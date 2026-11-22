<?php

namespace App\Http\Requests;


use App\Support\Envio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Vanilo\Payment\Models\PaymentMethod;
use Vanilo\Shipment\Models\ShippingMethodProxy;

class CheckoutRequest extends FormRequest
{
    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            'billpayer.firstname' => 'required|min:2|max:255',
            'billpayer.lastname'  => 'required|min:2|max:255',
            'billpayer.company_name' => 'required_if:billpayer.is_organization,1',
            'billpayer.address.address' => 'required|min:2|max:255',
            'billpayer.address.province_id' => [
                'nullable', 'required_if:billpayer.address.country_id,GT',
                Rule::exists('provinces', 'id')->where('country_id', $this->input('billpayer.address.country_id')),
            ],
            'shippingAddress.address' => 'required_unless:ship_to_billing_address,1',
            'shippingAddress.province_id' => [
                'nullable',
                Rule::requiredIf(fn () => !$this->shipsToBilling() && $this->input('shippingAddress.country_id') === 'GT'),
                Rule::exists('provinces', 'id')->where('country_id', $this->input('shippingAddress.country_id')),
            ],
            // Si hay tarifas configuradas en el admin, hay que elegir una
            'shipping_method_id' => [
                Rule::requiredIf(fn () => ShippingMethodProxy::actives()->exists()),
                'nullable', 'integer',
            ],
            'paymentMethod' => 'required|exists:payment_methods,id'
        ];
    }

    public function messages()
    {
        return [
            'billpayer.address.province_id.required_if' => __('Selecciona el departamento.'),
            'shippingAddress.province_id.required' => __('Selecciona el departamento de envío.'),
            'shipping_method_id.required' => __('Elige una opción de envío.'),
        ];
    }

    /** El courier elegido tiene que llegar al departamento de destino */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->filled('shipping_method_id') || $validator->errors()->isNotEmpty()) {
                    return;
                }
                [$country, $province] = $this->destination();
                if (!Envio::llegaA($this->input('shipping_method_id'), $country, $province)) {
                    $validator->errors()->add('shipping_method_id', __('Esa opción de envío no llega al departamento elegido.'));
                }
            },
        ];
    }

    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::find($this->get('paymentMethod'));
    }

    /** @return array{0: ?string, 1: ?int} país y departamento a donde va el pedido */
    public function destination(): array
    {
        $prefix = $this->shipsToBilling() ? 'billpayer.address' : 'shippingAddress';
        $province = $this->input("$prefix.province_id");

        return [$this->input("$prefix.country_id"), $province ? (int) $province : null];
    }

    private function shipsToBilling(): bool
    {
        return (bool) $this->input('ship_to_billing_address');
    }

    /**
     * @inheritDoc
     */
    public function authorize()
    {
        return true;
    }
}
