{{--
    Selector de departamento de Guatemala.
    Se muestra solo cuando el país elegido es Guatemala (country-select = name del select de país en el mismo formulario).
    Uso: <x-departamento name="province_id" :value="$addr->province_id" country-select="country_id"/>
--}}
@props(['name' => 'province_id', 'value' => null, 'countrySelect' => 'country_id', 'label' => 'Departamento', 'inputClass' => 'form-select form-control', 'required' => true])
@php
    $id = 'dep-' . \Illuminate\Support\Str::slug($name . '-' . $countrySelect);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
@endphp
<div {{ $attributes->merge(['class' => 'js-departamento']) }} data-country-select="{{ $countrySelect }}" data-required="{{ $required ? 1 : 0 }}">
    <label for="{{ $id }}" class="fw-bold">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id }}" class="{{ $inputClass }} {{ $errors->has($errorKey) ? 'is-invalid' : '' }}" data-departamento>
        <option value="">{{ __('Selecciona el departamento') }}</option>
        @foreach (\App\Support\Envio::departamentos() as $dep)
            <option value="{{ $dep->id }}" @selected((string) old($errorKey, $value) === (string) $dep->id)>{{ $dep->name }}</option>
        @endforeach
    </select>
    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
@once
    @push('scripts')
    <script>
        // Muestra el departamento solo si el país es Guatemala
        document.querySelectorAll('.js-departamento').forEach(function (box) {
            var scope = box.closest('form') || document;
            var pais = scope.querySelector('[name="' + box.dataset.countrySelect + '"]');
            var sel = box.querySelector('select');
            function toggle() {
                var esGT = !pais || pais.value === 'GT';
                box.style.display = esGT ? '' : 'none';
                sel.required = esGT && box.dataset.required === '1';
                if (!esGT) { sel.value = ''; }
                sel.dispatchEvent(new Event('change'));
            }
            if (pais) { pais.addEventListener('change', toggle); }
            toggle();
        });
    </script>
    @endpush
@endonce
