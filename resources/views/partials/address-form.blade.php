{{--
    Formulario de dirección (sirve para agregar y para editar)
    Guardar en: resources/views/partials/address-form.blade.php

    Variables que recibe:
      $action     URL a la que se envía el formulario
      $method     'POST' para agregar, 'PUT' para editar
      $addr       la dirección a editar, o null si es nueva
      $isDefault  true si la dirección ya es la predeterminada
      $countries  lista de países
--}}

@php
    $addrType    = $addr ? $addr->type->value() : 'shipping';
    $currentType = old('type', $addrType);
    $currentCountry = old('country_id', $addr ? $addr->country_id : 'GT');
    $defaultName = $addr ? $addr->name : Auth::user()->name;
@endphp

<form method="POST" action="{{ $action }}" class="border rounded p-3 mb-4 bg-light">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <h6 class="mb-3">{{ $addr ? 'Editar dirección' : 'Nueva dirección' }}</h6>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="type" class="fw-bold">Tipo de dirección</label>
            <select name="type" id="type" class="form-select form-control">
                <option value="shipping" {{ $currentType == 'shipping' ? 'selected' : '' }}>Envío</option>
                <option value="billing" {{ $currentType == 'billing' ? 'selected' : '' }}>Facturación</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="name" class="fw-bold">Nombre de quien recibe</label>
            <input type="text" name="name" id="name" class="form-control" required
                   value="{{ old('name', $defaultName) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="phone" class="fw-bold">Teléfono</label>
            <input type="text" name="phone" id="phone" class="form-control"
                   value="{{ old('phone', $addr->phone ?? '') }}">
        </div>

        <div class="col-md-8 mb-3">
            <label for="address" class="fw-bold">Dirección</label>
            <input type="text" name="address" id="address" class="form-control" required
                   placeholder="Calle, avenida, número de casa"
                   value="{{ old('address', $addr->address ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="address2" class="fw-bold">Zona / Referencia</label>
            <input type="text" name="address2" id="address2" class="form-control"
                   value="{{ old('address2', $addr->address2 ?? '') }}">
        </div>

        <x-departamento class="col-md-4 mb-3" name="province_id" :value="$addr->province_id ?? null" country-select="country_id"/>
        <div class="col-md-4 mb-3">
            <label for="city" class="fw-bold">Ciudad / Municipio</label>
            <input type="text" name="city" id="city" class="form-control" required
                   value="{{ old('city', $addr->city ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="postalcode" class="fw-bold">Código postal</label>
            <input type="text" name="postalcode" id="postalcode" class="form-control"
                   value="{{ old('postalcode', $addr->postalcode ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="country_id" class="fw-bold">País</label>
            <select name="country_id" id="country_id" class="form-select form-control" required>
                <option value="">Selecciona un país</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" {{ $currentCountry == $country->id ? 'selected' : '' }}>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label for="tax_nr" class="fw-bold">NIT <small class="text-muted">(para facturación)</small></label>
            <input type="text" name="tax_nr" id="tax_nr" class="form-control"
                   value="{{ old('tax_nr', $addr->tax_nr ?? '') }}">
        </div>
        <div class="col-md-8 mb-3 d-flex align-items-end">
            <label class="mb-2">
                <input type="checkbox" name="is_default" value="1"
                       {{ old('is_default', $isDefault) ? 'checked' : '' }}>
                Usar como dirección predeterminada de este tipo
            </label>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('home', ['section' => 'addresses']) }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-danger">{{ $addr ? 'Actualizar' : 'Guardar dirección' }}</button>
    </div>
</form>
