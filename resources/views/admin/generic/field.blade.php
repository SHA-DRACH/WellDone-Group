@php
    $type = $field['type'] ?? 'input';
    $label = $field['label'] ?? \Illuminate\Support\Str::of($name)->replace('_', ' ')->title()->toString();
    $value = $field['value'] ?? data_get($record, $name);
@endphp

@switch($type)
    @case('textarea')
        <x-form.textarea :name="$name" :label="$label" :value="$value"
                         :rows="$field['rows'] ?? 4"
                         :required="$field['required'] ?? false"
                         :help="$field['help'] ?? null"
                         :placeholder="$field['placeholder'] ?? null" />
        @break

    @case('editor')
        <x-form.editor :name="$name" :label="$label" :value="$value"
                       :required="$field['required'] ?? false"
                       :help="$field['help'] ?? 'Formatting is preserved. Pasted markup is sanitised before it is saved.'" />
        @break

    @case('select')
        <x-form.select :name="$name" :label="$label" :value="$value"
                       :options="$field['options'] ?? []"
                       :placeholder="$field['placeholder'] ?? null"
                       :required="$field['required'] ?? false"
                       :help="$field['help'] ?? null" />
        @break

    @case('checkbox')
        <x-form.checkbox :name="$name" :label="$label" :value="(bool) $value" :help="$field['help'] ?? null" />
        @break

    @case('image')
        <x-form.image :name="$name" :label="$label"
                      :current="data_get($record, $field['preview'] ?? $name.'_url')"
                      :help="$field['help'] ?? 'JPG, PNG, WebP, GIF or SVG. Large images are resized automatically.'" />
        @break

    @case('file')
        <div class="mb-3">
            <label class="form-label" for="field-{{ $name }}">{{ $label }}</label>
            <input type="file" id="field-{{ $name }}" name="{{ $name }}"
                   class="form-control @error($name) is-invalid @enderror"
                   @if(! empty($field['accept'])) accept="{{ $field['accept'] }}" @endif>
            @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if(! empty($field['help']))<div class="form-text">{{ $field['help'] }}</div>@endif
            @if($value)
                <div class="form-text">
                    Current: <code>{{ basename($value) }}</code>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="remove_{{ $name }}"
                               value="1" id="remove-{{ $name }}">
                        <label class="form-check-label small text-danger" for="remove-{{ $name }}">
                            Remove the current file
                        </label>
                    </div>
                </div>
            @endif
        </div>
        @break

    @case('lines')
        {{-- JSON array column edited as one item per line --}}
        <x-form.textarea :name="$name" :label="$label"
                         :value="is_array($value) ? implode(PHP_EOL, $value) : $value"
                         :rows="$field['rows'] ?? 5"
                         :help="$field['help'] ?? 'One item per line.'" />
        @break

    @case('pairs')
        {{-- JSON object column edited as `Label: value` per line --}}
        <x-form.textarea :name="$name" :label="$label"
                         :value="is_array($value) ? collect($value)->map(fn ($v, $k) => $k.': '.$v)->implode(PHP_EOL) : $value"
                         :rows="$field['rows'] ?? 5"
                         :help="$field['help'] ?? 'One per line, in the form `Label: value`.'" />
        @break

    @default
        <x-form.input :name="$name" :label="$label" :value="$value"
                      :type="$field['input'] ?? 'text'"
                      :required="$field['required'] ?? false"
                      :help="$field['help'] ?? null"
                      :placeholder="$field['placeholder'] ?? null"
                      @if(! empty($field['slug_from'])) data-slug-from="{{ $field['slug_from'] }}" @endif
                      @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                      @if(isset($field['max'])) max="{{ $field['max'] }}" @endif />
@endswitch
