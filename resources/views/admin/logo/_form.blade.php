@php $principal = (bool) old('es_principal', $logo->es_principal); @endphp

<div class="mb-3">
    <label class="form-label fw-bold" for="nombre">{{ __('Nombre') }}</label>
    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
           value="{{ old('nombre', $logo->nombre) }}" placeholder="{{ __('Ej. Navidad, Halloween, Semana Santa') }}" required maxlength="100">
    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="imagen">{{ __('Imagen del logo') }}</label>
    @if($logo->exists)
        <div class="mb-2" style="background:#12808c; border-radius:8px; padding:10px 14px; display:inline-block;">
            <img src="{{ $logo->url() }}" alt="" style="max-height:44px; max-width:220px;">
        </div>
        <div class="form-text mb-1">{{ __('Sube otra imagen solo si quieres reemplazarla.') }}</div>
    @endif
    <input type="file" name="imagen" id="imagen" accept="image/png,image/jpeg,image/webp,image/gif"
           class="form-control @error('imagen') is-invalid @enderror" @required(!$logo->exists)>
    <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">
        <strong>{{ __('Formatos aceptados:') }}</strong> {{ __('PNG, JPG, WEBP o GIF · máximo 2 MB.') }}<br>
        <strong>{{ __('SVG no se acepta') }}</strong> {{ __('por seguridad (puede contener código). Si tu logo está en SVG, expórtalo como PNG
        desde tu programa de diseño (Canva, Illustrator, Figma...).') }}<br>
        {{ __('Recomendado: PNG con fondo transparente, de unos 300 × 80 px. Se muestra sobre el fondo celeste de la barra.') }}
    </div>
    <div id="imagen-error" class="text-danger small mt-1" style="display:none"></div>
    @error('imagen') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    <div id="preview" class="mt-2" style="display:none; background:#12808c; border-radius:8px; padding:10px 14px;">
        <img alt="{{ __('Vista previa') }}" style="max-height:44px; max-width:220px;">
    </div>
</div>

<div class="form-check form-switch mb-3">
    <input type="hidden" name="es_principal" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="es_principal" value="1" id="es_principal" @checked($principal)>
    <label class="form-check-label" for="es_principal"><strong>{{ __('Logo principal') }}</strong> {{ __('(se usa todo el año cuando no hay uno de temporada)') }}</label>
</div>

<div id="fechas" @if($principal) style="display:none" @endif>
    <div class="row g-3 mb-2">
        <div class="col-md-6">
            <label class="form-label fw-bold" for="desde">{{ __('Se muestra desde') }}</label>
            <input type="date" name="desde" id="desde" class="form-control @error('desde') is-invalid @enderror"
                   value="{{ old('desde', $logo->desde?->format('Y-m-d')) }}">
            @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold" for="hasta">{{ __('Hasta') }}</label>
            <input type="date" name="hasta" id="hasta" class="form-control @error('hasta') is-invalid @enderror"
                   value="{{ old('hasta', $logo->hasta?->format('Y-m-d')) }}">
            @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="form-check mb-3">
        <input type="hidden" name="cada_anio" value="0">
        <input class="form-check-input" type="checkbox" name="cada_anio" value="1" id="cada_anio" @checked(old('cada_anio', $logo->cada_anio))>
        <label class="form-check-label" for="cada_anio">{{ __('Repetir cada año en las mismas fechas (ej. del 1 de diciembre al 6 de enero)') }}</label>
    </div>
</div>

<div class="form-check form-switch">
    <input type="hidden" name="activo" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="activo" value="1" id="activo" @checked(old('activo', $logo->activo))>
    <label class="form-check-label" for="activo">{{ __('Activo') }}</label>
</div>

@push('scripts')
<script>
    document.getElementById('es_principal').addEventListener('change', function () {
        document.getElementById('fechas').style.display = this.checked ? 'none' : '';
    });
    // Avisa al momento de elegir el archivo si el formato o el tamaño no sirven
    document.getElementById('imagen').addEventListener('change', function () {
        var box = document.getElementById('preview');
        var error = document.getElementById('imagen-error');
        var file = this.files && this.files[0];
        error.style.display = 'none';
        box.style.display = 'none';
        if (!file) { return; }

        var extension = (file.name.split('.').pop() || '').toLowerCase();
        var mensaje = null;
        if (['png', 'jpg', 'jpeg', 'webp', 'gif'].indexOf(extension) === -1) {
            mensaje = extension === 'svg'
                ? @json(__('Los archivos SVG no se aceptan. Exporta tu logo como PNG y vuelve a subirlo.'))
                : @json(__('El archivo ".:ext" no es válido. Usa PNG, JPG, WEBP o GIF.')).replace(':ext', extension);
        } else if (file.size > 2 * 1024 * 1024) {
            mensaje = @json(__('La imagen pesa :mb MB. El máximo es 2 MB.')).replace(':mb', (file.size / 1024 / 1024).toFixed(1));
        }

        if (mensaje) {
            error.textContent = mensaje;
            error.style.display = 'block';
            this.value = '';
            return;
        }
        box.querySelector('img').src = URL.createObjectURL(file);
        box.style.display = 'inline-block';
    });
</script>
@endpush
