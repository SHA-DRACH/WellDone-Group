<?php

namespace App\Support\DivisionRecords;

use App\Models\Division;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * What each division writes in its daily log.
 *
 * The four divisions do very different work, so one form for all of them
 * would be either too vague to be useful or a wall of irrelevant fields. The
 * fields are declared here per division — matched on the division's slug —
 * and drive everything else: the entry form on the dashboard, validation,
 * the table, the monthly totals and the Excel columns.
 *
 * A division added later from the CMS falls back to a general log until it
 * is given a schema of its own here.
 *
 * Every record also carries a date, a reference and notes; those are
 * columns on the table, not fields in a schema.
 *
 * Field options:
 *   type      text | textarea | integer | number | money | date | select
 *   options   for select
 *   required  bool
 *   total     bool — summed in the monthly figures and the Excel totals row
 *   list      bool — shown as a column in the on-screen table (default true)
 *   unit      suffix shown after a number, e.g. "t" or "L"
 *   col       Bootstrap column width in the entry form (default 4)
 *   product   [a, b] — a read-only value worked out as a × b
 */
final class RecordSchema
{
    /** @return array<string, mixed> */
    public static function for(Division $division): array
    {
        $schema = self::definitions()[$division->slug] ?? self::general();

        $schema['fields'] = collect($schema['fields'])
            ->map(fn (array $field) => $field + ['type' => 'text', 'required' => false, 'list' => true, 'col' => 4])
            ->all();

        $schema['computed'] ??= [];
        $schema['prefix'] ??= self::prefixFor($division);

        return $schema;
    }

    /** Validation rules for the record's own columns plus its schema fields. */
    public static function rules(array $schema): array
    {
        $rules = [
            'record_date' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($schema['fields'] as $name => $field) {
            $base = $field['required'] ? ['required'] : ['nullable'];

            $rules['data.'.$name] = array_merge($base, match ($field['type']) {
                'textarea' => ['string', 'max:2000'],
                'integer' => ['integer', 'min:0', 'max:100000000'],
                'number', 'money' => ['numeric', 'min:0', 'max:1000000000000'],
                'date' => ['date'],
                'select' => [Rule::in($field['options'] ?? [])],
                default => ['string', 'max:255'],
            });
        }

        return $rules;
    }

    /** Friendly names for validation messages ("The tonnes extracted field…"). */
    public static function attributes(array $schema): array
    {
        return collect($schema['fields'])
            ->mapWithKeys(fn (array $field, string $name) => ['data.'.$name => Str::lower($field['label'])])
            ->put('record_date', 'date')
            ->all();
    }

    /**
     * Keep only declared fields, cast numbers, and work out products — so
     * nothing a form did not ask for is ever stored.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function clean(array $schema, array $input): array
    {
        $data = [];

        foreach ($schema['fields'] as $name => $field) {
            $value = $input[$name] ?? null;

            if ($value === null || $value === '') {
                $data[$name] = null;

                continue;
            }

            $data[$name] = match ($field['type']) {
                'integer' => (int) $value,
                'number', 'money' => round((float) $value, 2),
                default => is_string($value) ? trim($value) : $value,
            };
        }

        foreach ($schema['computed'] as $name => $computed) {
            [$a, $b] = $computed['product'];

            $data[$name] = isset($data[$a], $data[$b]) ? round($data[$a] * $data[$b], 2) : null;
        }

        return $data;
    }

    /** Every column, schema fields then computed ones, for tables and Excel. */
    public static function columns(array $schema): array
    {
        return $schema['fields'] + collect($schema['computed'])
            ->map(fn (array $c) => $c + ['list' => true, 'required' => false])
            ->all();
    }

    /** Fields that are summed. */
    public static function totals(array $schema): array
    {
        return array_keys(array_filter(self::columns($schema), fn (array $c) => ! empty($c['total'])));
    }

    /** "mining-quarry" → "MQ". Used in record references, e.g. MQ-2026-00012. */
    public static function prefixFor(Division $division): string
    {
        $initials = collect(preg_split('/[-\s]+/', (string) $division->slug))
            ->filter()
            ->map(fn (string $word) => Str::upper($word[0]))
            ->implode('');

        return Str::limit($initials ?: 'DV', 4, '');
    }

    // ------------------------------------------------------------------

    /** @return array<string, array<string, mixed>> */
    private static function definitions(): array
    {
        return [
            'mining-quarry' => [
                'title' => 'Daily Production Log',
                'singular' => 'production entry',
                'icon' => 'fa-solid fa-helmet-safety',
                'intro' => 'One entry per site, per shift: what was extracted and dispatched, and what it took.',
                'fields' => [
                    'site' => ['label' => 'Site / pit', 'required' => true, 'placeholder' => 'e.g. Kakata quarry, Pit 2'],
                    'shift' => ['label' => 'Shift', 'type' => 'select', 'options' => ['Day', 'Night'], 'required' => true, 'col' => 2],
                    'material' => ['label' => 'Material', 'type' => 'select', 'required' => true, 'col' => 3,
                        'options' => ['Crushed stone', 'Granite aggregate', 'Gravel', 'Laterite', 'Sand', 'Iron ore', 'Gold-bearing ore', 'Other']],
                    'crew' => ['label' => 'Crew on shift', 'type' => 'integer', 'col' => 3],
                    'tonnes_extracted' => ['label' => 'Tonnes extracted', 'type' => 'number', 'unit' => 't', 'total' => true, 'required' => true, 'col' => 3],
                    'tonnes_dispatched' => ['label' => 'Tonnes dispatched', 'type' => 'number', 'unit' => 't', 'total' => true, 'col' => 3],
                    'truck_loads' => ['label' => 'Truck loads', 'type' => 'integer', 'total' => true, 'col' => 2],
                    'equipment_hours' => ['label' => 'Equipment hours', 'type' => 'number', 'unit' => 'h', 'total' => true, 'col' => 2],
                    'fuel_litres' => ['label' => 'Fuel used', 'type' => 'number', 'unit' => 'L', 'total' => true, 'col' => 2],
                    'incidents' => ['label' => 'Safety incidents', 'type' => 'integer', 'total' => true, 'col' => 2],
                ],
            ],

            'procurement-logistics' => [
                'title' => 'Shipment Register',
                'singular' => 'shipment',
                'icon' => 'fa-solid fa-truck-fast',
                'intro' => 'Every consignment moved: where from, where to, what, and where it stands.',
                'fields' => [
                    'waybill' => ['label' => 'Waybill / PO no.', 'required' => true, 'col' => 3],
                    'client' => ['label' => 'Client', 'required' => true, 'col' => 5],
                    'status' => ['label' => 'Status', 'type' => 'select', 'required' => true, 'col' => 4,
                        'options' => ['Booked', 'In transit', 'Delivered', 'Delayed', 'Cancelled']],
                    'origin' => ['label' => 'Origin', 'required' => true],
                    'destination' => ['label' => 'Destination', 'required' => true],
                    'cargo' => ['label' => 'Cargo', 'required' => true, 'placeholder' => 'e.g. 40 × cement bags'],
                    'weight_kg' => ['label' => 'Weight', 'type' => 'number', 'unit' => 'kg', 'total' => true, 'col' => 3],
                    'vehicle' => ['label' => 'Truck / vehicle no.', 'col' => 3],
                    'driver' => ['label' => 'Driver', 'col' => 3, 'list' => false],
                    'delivered_on' => ['label' => 'Delivered on', 'type' => 'date', 'col' => 3, 'list' => false],
                    'freight_charge' => ['label' => 'Freight charge', 'type' => 'money', 'total' => true, 'col' => 3],
                ],
            ],

            'medical-equipment' => [
                'title' => 'Stock & Delivery Log',
                'singular' => 'stock movement',
                'icon' => 'fa-solid fa-kit-medical',
                'intro' => 'Stock received, delivered and returned — with batch numbers and expiry dates kept for traceability.',
                'fields' => [
                    'movement' => ['label' => 'Movement', 'type' => 'select', 'required' => true, 'col' => 3,
                        'options' => ['Received', 'Delivered to client', 'Returned', 'Stock adjustment']],
                    'item' => ['label' => 'Item / equipment', 'required' => true, 'col' => 5],
                    'category' => ['label' => 'Category', 'type' => 'select', 'col' => 4,
                        'options' => ['Diagnostic equipment', 'Surgical instruments', 'Consumables', 'PPE', 'Laboratory supplies', 'Hospital furniture', 'Spare parts', 'Other']],
                    'quantity' => ['label' => 'Quantity', 'type' => 'integer', 'required' => true, 'total' => true, 'col' => 2],
                    'unit' => ['label' => 'Unit', 'type' => 'select', 'col' => 2, 'list' => false,
                        'options' => ['Units', 'Boxes', 'Cartons', 'Packs', 'Sets']],
                    'unit_cost' => ['label' => 'Unit cost', 'type' => 'money', 'col' => 2],
                    'batch_serial' => ['label' => 'Batch / serial no.', 'col' => 3],
                    'expiry_date' => ['label' => 'Expiry date', 'type' => 'date', 'col' => 3],
                    'facility' => ['label' => 'Supplier or facility', 'col' => 6],
                ],
                'computed' => [
                    'line_value' => ['label' => 'Value', 'type' => 'money', 'product' => ['quantity', 'unit_cost'], 'total' => true],
                ],
            ],

            'ict-services' => [
                'title' => 'Service & Support Log',
                'singular' => 'job',
                'icon' => 'fa-solid fa-network-wired',
                'intro' => 'Support tickets, installations and maintenance visits — who did what, for whom, and what was billed.',
                'fields' => [
                    'client' => ['label' => 'Client', 'required' => true, 'col' => 4],
                    'job_type' => ['label' => 'Job type', 'type' => 'select', 'required' => true, 'col' => 4,
                        'options' => ['Support ticket', 'Installation', 'Networking', 'CCTV & security', 'Software / systems', 'Maintenance', 'Training']],
                    'priority' => ['label' => 'Priority', 'type' => 'select', 'col' => 2,
                        'options' => ['Low', 'Normal', 'High', 'Critical']],
                    'status' => ['label' => 'Status', 'type' => 'select', 'required' => true, 'col' => 2,
                        'options' => ['Open', 'In progress', 'Resolved', 'Closed']],
                    'description' => ['label' => 'Job description', 'required' => true, 'col' => 6],
                    'technician' => ['label' => 'Technician', 'col' => 3],
                    'completed_on' => ['label' => 'Completed on', 'type' => 'date', 'col' => 3, 'list' => false],
                    'hours' => ['label' => 'Hours spent', 'type' => 'number', 'unit' => 'h', 'total' => true, 'col' => 3],
                    'amount_billed' => ['label' => 'Amount billed', 'type' => 'money', 'total' => true, 'col' => 3],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function general(): array
    {
        return [
            'title' => 'Operations Log',
            'singular' => 'entry',
            'icon' => 'fa-solid fa-clipboard-list',
            'intro' => 'A running record of the division\'s work.',
            'fields' => [
                'category' => ['label' => 'Category', 'required' => true, 'col' => 4],
                'description' => ['label' => 'Description', 'required' => true, 'col' => 8],
                'quantity' => ['label' => 'Quantity', 'type' => 'number', 'total' => true],
                'amount' => ['label' => 'Amount', 'type' => 'money', 'total' => true],
                'responsible' => ['label' => 'Responsible person'],
            ],
        ];
    }
}
