<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionRecord;
use App\Support\DivisionRecords\RecordSchema;
use App\Support\DivisionScope;
use App\Support\Excel\Workbook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * A division's operations log: the entry form on its dashboard, the full
 * list, and the Excel download.
 *
 * Every action takes the division from the URL and checks it against the
 * signed-in user before anything else, so a mining officer who edits the
 * address to /division/ict-services/records gets a 403, not ICT's log.
 */
class DivisionRecordController extends Controller
{
    public function index(Request $request, Division $division): View
    {
        $this->authorize('viewAny', DivisionRecord::class);
        $this->assertDivision($division);

        $schema = RecordSchema::for($division);
        $query = $this->filtered($request, $division, $schema);

        return view('admin.division-records.index', [
            'division' => $division,
            'schema' => $schema,
            'columns' => RecordSchema::columns($schema),
            'records' => (clone $query)->with('creator:id,name')->ordered()->paginate(25)->withQueryString(),
            'totals' => self::sumFields($query, RecordSchema::totals($schema)),
            'count' => (clone $query)->count(),
            'filters' => $request->only(['from', 'to', 'q', 'field']),
        ]);
    }

    public function create(Division $division): View
    {
        $this->authorize('create', DivisionRecord::class);
        $this->assertDivision($division, write: true);

        return view('admin.division-records.form', [
            'division' => $division,
            'schema' => RecordSchema::for($division),
            'record' => new DivisionRecord(['record_date' => today()]),
        ]);
    }

    public function store(Request $request, Division $division): RedirectResponse
    {
        $this->authorize('create', DivisionRecord::class);
        $this->assertDivision($division, write: true);

        $schema = RecordSchema::for($division);
        $validated = $request->validate(RecordSchema::rules($schema), [], RecordSchema::attributes($schema));

        $record = DivisionRecord::query()->create([
            'division_id' => $division->id,
            'record_date' => $validated['record_date'],
            'data' => RecordSchema::clean($schema, $validated['data'] ?? []),
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return $this->back($request, $division)
            ->with('success', ucfirst($schema['singular']).' '.$record->reference.' saved.');
    }

    public function edit(Division $division, DivisionRecord $record): View
    {
        $this->authorize('update', $record);
        $this->assertDivision($division, write: true);

        return view('admin.division-records.form', [
            'division' => $division,
            'schema' => RecordSchema::for($division),
            'record' => $record,
        ]);
    }

    public function update(Request $request, Division $division, DivisionRecord $record): RedirectResponse
    {
        $this->authorize('update', $record);
        $this->assertDivision($division, write: true);

        $schema = RecordSchema::for($division);
        $validated = $request->validate(RecordSchema::rules($schema), [], RecordSchema::attributes($schema));

        $record->update([
            'record_date' => $validated['record_date'],
            'data' => RecordSchema::clean($schema, $validated['data'] ?? []),
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.division.records.index', $division)
            ->with('success', $record->reference.' updated.');
    }

    public function destroy(Division $division, DivisionRecord $record): RedirectResponse
    {
        $this->authorize('delete', $record);
        $this->assertDivision($division, write: true);

        $record->delete();

        return redirect()
            ->route('admin.division.records.index', $division)
            ->with('success', $record->reference.' deleted.');
    }

    /** The current filters, as a spreadsheet. */
    public function export(Request $request, Division $division): BinaryFileResponse
    {
        $this->authorize('export', DivisionRecord::class);
        $this->assertDivision($division);

        $schema = RecordSchema::for($division);
        $columns = RecordSchema::columns($schema);

        $sheetColumns = array_merge(
            [
                ['key' => 'reference', 'label' => 'Reference', 'width' => 18],
                ['key' => 'record_date', 'label' => 'Date', 'type' => 'date'],
            ],
            collect($columns)->map(fn (array $field, string $name) => [
                'key' => $name,
                'label' => $field['label'].(isset($field['unit']) ? ' ('.$field['unit'].')' : ''),
                'type' => match ($field['type']) {
                    'integer' => 'integer',
                    'number' => 'number',
                    'money' => 'money',
                    'date' => 'date',
                    default => 'text',
                },
                'total' => ! empty($field['total']),
                'wrap' => $field['type'] === 'textarea',
            ])->values()->all(),
            [
                ['key' => 'notes', 'label' => 'Notes', 'width' => 40, 'wrap' => true],
                ['key' => 'entered_by', 'label' => 'Entered by'],
                ['key' => 'entered_at', 'label' => 'Entered at', 'type' => 'datetime'],
            ],
        );

        $rows = $this->filtered($request, $division, $schema)
            ->with('creator:id,name')
            ->orderBy('record_date')
            ->orderBy('id')
            ->lazy(500)
            ->map(function (DivisionRecord $record) use ($columns) {
                $row = [
                    'reference' => $record->reference,
                    'record_date' => $record->record_date,
                    'notes' => $record->notes,
                    'entered_by' => $record->creator?->name,
                    'entered_at' => $record->created_at,
                ];

                foreach ($columns as $name => $field) {
                    $value = $record->field($name);
                    $row[$name] = $field['type'] === 'date' && $value ? \Illuminate\Support\Carbon::parse($value) : $value;
                }

                return $row;
            });

        $period = collect([$request->query('from'), $request->query('to')])->filter()->implode(' to ');

        return Workbook::make($division->name.' '.$schema['title'], $division->name.' — '.$schema['title'])
            ->sheet($schema['title'], $sheetColumns, $rows, subtitle: $period ? 'Period: '.$period : 'All dates')
            ->download();
    }

    // ------------------------------------------------------------------

    /** The list query with the screen's filters applied. */
    protected function filtered(Request $request, Division $division, array $schema): Builder
    {
        $query = DivisionRecord::query()
            ->withinDivisionOf($request->user())
            ->where('division_id', $division->id)
            ->between($request->query('from'), $request->query('to'))
            ->search(trim((string) $request->query('q')));

        // One exact-match filter on a select field, e.g. status = Delivered.
        foreach ((array) $request->query('field', []) as $name => $value) {
            $field = $schema['fields'][$name] ?? null;

            if ($field && $field['type'] === 'select' && in_array($value, $field['options'], true)) {
                $query->where('data->'.$name, $value);
            }
        }

        return $query;
    }

    /**
     * Sum JSON fields over a query. Done in PHP rather than with JSON_EXTRACT
     * so it gives the same answer on MySQL, MariaDB and SQLite.
     *
     * @return array<string, float>
     */
    public static function sumFields(Builder $query, array $fields): array
    {
        $totals = array_fill_keys($fields, 0.0);

        if ($fields === []) {
            return $totals;
        }

        foreach ((clone $query)->toBase()->select('data')->cursor() as $row) {
            $data = is_array($row->data) ? $row->data : (json_decode((string) $row->data, true) ?: []);

            foreach ($fields as $field) {
                $totals[$field] += (float) ($data[$field] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * The same rule as the dashboard: division staff may only open their own
     * division. Group roles may open any, but writing to a switched-off
     * division is refused for everybody.
     */
    protected function assertDivision(Division $division, bool $write = false): void
    {
        DivisionScope::assertVisible(auth()->user(), $division->id, 'division');

        if (DivisionScope::restricts(auth()->user()) || $write) {
            abort_unless($division->is_active, 404);
        }
    }

    protected function back(Request $request, Division $division): RedirectResponse
    {
        return $request->input('return') === 'dashboard'
            ? redirect()->route('admin.division.dashboard', $division)->withFragment('operations-log')
            : redirect()->route('admin.division.records.index', $division);
    }
}
