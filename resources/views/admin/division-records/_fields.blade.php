{{--
    The entry fields for one division's log, built from its schema.
    Shared by the quick-entry card on the dashboard and the full form.

    Expects: $schema, $record (a DivisionRecord, possibly unsaved).
--}}
{{-- Block form on purpose: an inline @php() before a later @php…@endphp
     block makes Blade pair the wrong tags and the block goes uncompiled. --}}
@php
    $idPrefix = $idPrefix ?? 'rec';
@endphp

<div class="row g-3">
    <div class="col-sm-6 col-lg-3">
        <label class="form-label" for="{{ $idPrefix }}-date">Date <span class="required-mark">*</span></label>
        <input type="date" id="{{ $idPrefix }}-date" name="record_date" required
               max="{{ now()->addDay()->toDateString() }}"
               value="{{ old('record_date', $record->record_date?->toDateString() ?? today()->toDateString()) }}"
               class="form-control @error('record_date') is-invalid @enderror">
        @error('record_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @foreach($schema['fields'] as $name => $field)
        @php
            $key = 'data.'.$name;
            $inputName = 'data['.$name.']';
            $id = $idPrefix.'-'.$name;
            $value = old($key, $record->field($name));
            $col = $field['type'] === 'textarea' ? 12 : $field['col'];
        @endphp

        <div class="col-sm-6 col-lg-{{ $col }}">
            <label class="form-label" for="{{ $id }}">
                {{ $field['label'] }}@if($field['required']) <span class="required-mark">*</span>@endif
            </label>

            @if($field['type'] === 'select')
                <select id="{{ $id }}" name="{{ $inputName }}" @required($field['required'])
                        class="form-select @error($key) is-invalid @enderror">
                    <option value="">Choose…</option>
                    @foreach($field['options'] as $option)
                        <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            @elseif($field['type'] === 'textarea')
                <textarea id="{{ $id }}" name="{{ $inputName }}" rows="2" @required($field['required'])
                          class="form-control @error($key) is-invalid @enderror">{{ $value }}</textarea>
            @elseif(in_array($field['type'], ['integer', 'number', 'money'], true))
                <div class="input-group has-validation">
                    @if($field['type'] === 'money')<span class="input-group-text">$</span>@endif
                    <input type="number" id="{{ $id }}" name="{{ $inputName }}" value="{{ $value }}" min="0"
                           step="{{ $field['type'] === 'integer' ? 1 : '0.01' }}" inputmode="decimal"
                           @required($field['required'])
                           class="form-control @error($key) is-invalid @enderror">
                    @isset($field['unit'])<span class="input-group-text">{{ $field['unit'] }}</span>@endisset
                    @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @else
                <input type="{{ $field['type'] === 'date' ? 'date' : 'text' }}" id="{{ $id }}" name="{{ $inputName }}"
                       value="{{ $value }}" maxlength="255" @required($field['required'])
                       @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                       class="form-control @error($key) is-invalid @enderror">
            @endif

            @if(! in_array($field['type'], ['integer', 'number', 'money'], true))
                @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
    @endforeach

    <div class="col-12">
        <label class="form-label" for="{{ $idPrefix }}-notes">Notes</label>
        <textarea id="{{ $idPrefix }}-notes" name="notes" rows="2" maxlength="2000"
                  placeholder="Anything worth knowing — delays, breakdowns, client remarks"
                  class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $record->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
