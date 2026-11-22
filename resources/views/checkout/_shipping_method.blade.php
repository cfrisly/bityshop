{{--
    Opciones de envío (Admin → Métodos de envío). Se filtran según el país y departamento
    de destino: el de facturación si "Ship to the same address" está marcado, si no el de envío.
--}}
@if($shippingOptions->isNotEmpty())
<div id="shipping-method" class="mb-4">
    <h3>{{ __('Envío') }}</h3>
    <hr>

    <p class="text-muted js-envio-sin-destino">{{ __('Selecciona el departamento para ver las opciones de envío.') }}</p>
    <div class="alert alert-warning js-envio-sin-cobertura" style="display:none">
        {{ __('Por ahora no tenemos envío a ese destino. Escríbenos y te ayudamos a coordinarlo.') }}
    </div>

    <div class="list-group mb-2">
        @foreach($shippingOptions as $op)
            <label class="list-group-item js-envio-opcion" style="display:none; cursor:pointer"
                   data-paises='@json($op['paises'])' data-departamentos='@json($op['departamentos'])' data-costo="{{ $op['costo'] }}">
              <span class="d-flex gap-3 align-items-start">
                <input class="form-check-input mt-1" type="radio" name="shipping_method_id" value="{{ $op['id'] }}"
                       @checked((string) old('shipping_method_id') === (string) $op['id'])>
                <span class="flex-grow-1">
                    <strong>{{ $op['courier'] ?? $op['nombre'] }}</strong>
                    @if($op['courier'] && $op['courier'] !== $op['nombre']) <span class="text-muted">· {{ $op['nombre'] }}</span> @endif
                    @if($op['desde'])
                        <br><small class="text-muted">{{ $op['desde'] === $op['hasta'] ? __('Llega el :fecha', ['fecha' => $op['desde']]) : __('Llega entre :desde y :hasta', ['desde' => $op['desde'], 'hasta' => $op['hasta']]) }}</small>
                    @endif
                </span>
                <span class="fw-bold text-nowrap">
                    @if($op['costo'] > 0) {{ format_price($op['costo']) }} @else <span class="text-success">{{ __('Gratis') }}</span> @endif
                </span>
              </span>
            </label>
        @endforeach
    </div>

    @error('shipping_method_id')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('shipping-method').closest('form');
        var q = function (name) { return form.querySelector('[name="' + name + '"]'); };
        var opciones = Array.prototype.slice.call(document.querySelectorAll('.js-envio-opcion'));

        function destino() {
            var mismo = q('ship_to_billing_address') && form.querySelector('#chk_ship_to_billing_address').checked;
            var pre = mismo ? 'billpayer[address]' : 'shippingAddress';
            return {
                pais: (q(pre + '[country_id]') || {}).value || '',
                depto: parseInt((q(pre + '[province_id]') || {}).value || '0', 10)
            };
        }

        function actualizar() {
            var d = destino();
            var falta = d.pais === 'GT' && !d.depto;
            var visibles = 0;
            opciones.forEach(function (op) {
                var paises = JSON.parse(op.dataset.paises), deptos = JSON.parse(op.dataset.departamentos);
                var llega = !falta && (paises.indexOf(d.pais) !== -1 || deptos.indexOf(d.depto) !== -1);
                op.style.display = llega ? '' : 'none';
                if (!llega) { op.querySelector('input').checked = false; }
                if (llega) { visibles++; }
            });
            // Si no hay ninguna elegida, se marca la más barata (vienen ordenadas por precio)
            if (visibles && !form.querySelector('[name="shipping_method_id"]:checked')) {
                var primera = opciones.find(function (op) { return op.style.display !== 'none'; });
                primera.querySelector('input').checked = true;
            }
            document.querySelector('.js-envio-sin-destino').style.display = falta ? '' : 'none';
            document.querySelector('.js-envio-sin-cobertura').style.display = (!falta && !visibles) ? '' : 'none';
            actualizarResumen();
        }

        function actualizarResumen() {
            var elegido = form.querySelector('[name="shipping_method_id"]:checked');
            var linea = document.querySelector('[data-resumen-envio]');
            var total = document.querySelector('[data-resumen-total]');
            if (!linea || !total) { return; }
            var costo = elegido ? parseFloat(elegido.closest('.js-envio-opcion').dataset.costo) : null;
            var fmt = function (v) { return 'Q' + v.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
            linea.textContent = costo === null ? @json(__('Elige una opción')) : (costo > 0 ? fmt(costo) : @json(__('Gratis')));
            total.textContent = fmt(parseFloat(total.dataset.base) + (costo || 0));
        }

        form.addEventListener('change', function (e) {
            var n = e.target.name || '';
            if (n.indexOf('country_id') !== -1 || n.indexOf('province_id') !== -1 || e.target.id === 'chk_ship_to_billing_address') {
                actualizar();
            } else if (n === 'shipping_method_id') {
                actualizarResumen();
            }
        });
        actualizar();
    })();
</script>
@endpush
@endif
