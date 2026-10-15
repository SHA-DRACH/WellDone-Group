<?php

namespace App\Support\Excel;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Branded .xlsx exports, one sheet or several.
 *
 * Every Excel download in the back office goes through here, so they all
 * look the same: a title and "generated on" line, a navy header row that
 * stays frozen while scrolling, filter arrows on every column, proper number
 * and date formats, and a totals row built from live SUM formulas — edit a
 * figure in Excel and the total follows.
 *
 * Columns are declared once:
 *
 *     ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true]
 *
 * Types: text (default), integer, number, money, percent, date, datetime.
 *
 * Text is always written as a string cell. OpenSpout would otherwise treat
 * any value starting with "=" as a formula, and these sheets carry text that
 * staff and the public typed in — a description beginning "=HYPERLINK(…)"
 * must arrive in Excel as text, not as something Excel evaluates.
 */
final class Workbook
{
    private const BRAND_NAVY = '0B3C91';

    private const BRAND_GOLD = 'F2B705';

    private const HEADER_ROW = 4;

    /** @var array<int, array{name: string, columns: array, rows: iterable, title: ?string, subtitle: ?string}> */
    private array $sheets = [];

    private function __construct(
        private string $filename,
        private string $title,
    ) {}

    /**
     * @param  string  $filename  Without extension; a date is appended.
     */
    public static function make(string $filename, ?string $title = null): self
    {
        return new self(
            Str::slug($filename).'-'.now()->format('Y-m-d'),
            $title ?? Str::headline($filename),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  iterable<array<string, mixed>>  $rows  Each row keyed by column key.
     */
    public function sheet(string $name, array $columns, iterable $rows, ?string $title = null, ?string $subtitle = null): self
    {
        $this->sheets[] = [
            // Excel refuses sheet names over 31 characters or containing []:*?/\
            'name' => Str::limit(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $name), 31, ''),
            'columns' => array_values($columns),
            'rows' => $rows,
            'title' => $title,
            'subtitle' => $subtitle,
        ];

        return $this;
    }

    public function filename(): string
    {
        return $this->filename.'.xlsx';
    }

    /** Write the workbook to a path. Used by download() and by the tests. */
    public function save(string $path): string
    {
        $options = new Options;
        $options->setProperties(new Properties(
            title: $this->title,
            application: config('app.name'),
            creator: (string) (auth()->user()?->name ?? config('app.name')),
            lastModifiedBy: (string) (auth()->user()?->name ?? config('app.name')),
        ));

        $writer = new Writer($options);
        $writer->openToFile($path);

        foreach ($this->sheets as $index => $definition) {
            $sheet = $index === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($definition['name'] ?: 'Sheet '.($index + 1));

            $this->writeSheet($writer, $sheet, $definition);
        }

        $writer->close();

        return $path;
    }

    public function download(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        $this->save($path);

        return response()
            ->download($path, $this->filename(), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    // ------------------------------------------------------------------

    private function writeSheet(Writer $writer, $sheet, array $definition): void
    {
        $columns = $definition['columns'];
        $lastColumn = max(count($columns) - 1, 0);

        // Row 1–2: heading and provenance. Row 3 is a spacer.
        $writer->addRow(new Row([
            new StringCell($definition['title'] ?? $this->title,
                (new Style)->setFontBold()->setFontSize(15)->setFontColor(self::BRAND_NAVY)),
        ]));

        $writer->addRow(new Row([
            new StringCell(
                trim(($definition['subtitle'] ? $definition['subtitle'].' · ' : '')
                    .setting('company.name', config('app.name'))
                    .' · Generated '.now()->format('j M Y, H:i')
                    .(auth()->check() ? ' by '.auth()->user()->name : '')),
                (new Style)->setFontItalic()->setFontSize(10)->setFontColor('5B6475'),
            ),
        ]));

        $writer->addRow(new Row([]));

        // Row 4: header.
        $header = (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(self::BRAND_NAVY)
            ->setShouldWrapText()
            ->setBorder(new Border(new BorderPart(Border::BOTTOM, self::BRAND_GOLD, Border::WIDTH_MEDIUM)));

        $writer->addRow(new Row(array_map(
            fn (array $column) => new StringCell($column['label'], $header),
            $columns,
        )));

        // Data.
        $written = 0;

        foreach ($definition['rows'] as $row) {
            $striped = $written % 2 === 1;

            $writer->addRow(new Row(array_map(
                fn (array $column) => $this->cell($row[$column['key']] ?? null, $column, $striped),
                $columns,
            )));

            $written++;
        }

        $firstData = self::HEADER_ROW + 1;
        $lastData = self::HEADER_ROW + $written;

        // Totals, as formulas so they stay right when the sheet is edited.
        if ($written > 0 && collect($columns)->contains(fn ($c) => ! empty($c['total']))) {
            $totalStyle = fn (array $column) => $this->style($column, false)
                ->setFontBold()
                ->setBackgroundColor('E8EEF9')
                ->setBorder(new Border(new BorderPart(Border::TOP, self::BRAND_NAVY, Border::WIDTH_MEDIUM)));

            $cells = [];

            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $cells[] = new StringCell('Total', $totalStyle(['type' => 'text']));
                } elseif (! empty($column['total'])) {
                    $letter = self::columnLetter($index);
                    $cells[] = Cell::fromValue("=SUM({$letter}{$firstData}:{$letter}{$lastData})", $totalStyle($column));
                } else {
                    $cells[] = Cell::fromValue(null, $totalStyle(['type' => 'text']));
                }
            }

            $writer->addRow(new Row($cells));
        }

        if ($written === 0) {
            $writer->addRow(new Row([
                new StringCell('No records match the current filters.', (new Style)->setFontItalic()->setFontColor('5B6475')),
            ]));
        }

        // Frozen header, filter arrows, sensible widths.
        $sheet->setSheetView((new SheetView)->setFreezeRow(self::HEADER_ROW + 1)->setShowGridLines(true));

        if ($written > 0) {
            $sheet->setAutoFilter(new AutoFilter(0, self::HEADER_ROW, $lastColumn, $lastData));
        }

        foreach ($columns as $index => $column) {
            $sheet->setColumnWidth((float) ($column['width'] ?? $this->defaultWidth($column)), $index + 1);
        }
    }

    private function cell(mixed $value, array $column, bool $striped): Cell
    {
        $type = $column['type'] ?? 'text';
        $style = $this->style($column, $striped);

        if ($value === null || $value === '') {
            return Cell::fromValue(null, $style);
        }

        return match ($type) {
            'integer' => Cell::fromValue((int) $value, $style),
            'number', 'money', 'percent' => is_numeric($value)
                ? Cell::fromValue((float) $value, $style)
                : new StringCell((string) $value, $style),
            'date', 'datetime' => $value instanceof DateTimeInterface
                ? Cell::fromValue($value instanceof CarbonInterface ? $value->toDateTimeImmutable() : $value, $style)
                : new StringCell((string) $value, $style),
            default => new StringCell(is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value, $style),
        };
    }

    private function style(array $column, bool $striped): Style
    {
        $style = new Style;

        if ($striped) {
            $style->setBackgroundColor('F5F7FB');
        }

        match ($column['type'] ?? 'text') {
            'integer' => $style->setFormat('#,##0')->setCellAlignment(CellAlignment::RIGHT),
            'number' => $style->setFormat('#,##0.00')->setCellAlignment(CellAlignment::RIGHT),
            'money' => $style->setFormat('#,##0.00;[Red]-#,##0.00')->setCellAlignment(CellAlignment::RIGHT),
            'percent' => $style->setFormat('0.00"%"')->setCellAlignment(CellAlignment::RIGHT),
            'date' => $style->setFormat('dd mmm yyyy'),
            'datetime' => $style->setFormat('dd mmm yyyy hh:mm'),
            default => $style->setShouldWrapText(! empty($column['wrap'])),
        };

        return $style;
    }

    private function defaultWidth(array $column): float
    {
        return match ($column['type'] ?? 'text') {
            'integer', 'percent' => 12,
            'number', 'money' => 16,
            'date' => 14,
            'datetime' => 18,
            default => max(14, min(48, mb_strlen($column['label']) + 6)),
        };
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    public static function columnLetter(int $index): string
    {
        $letter = '';

        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letter = chr(65 + ($n - 1) % 26).$letter;
        }

        return $letter;
    }
}
