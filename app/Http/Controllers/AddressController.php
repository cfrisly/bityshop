<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Konekt\Address\Models\AddressTypeProxy;
use Konekt\Customer\Models\CustomerProxy;
use Konekt\Customer\Models\CustomerTypeProxy;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Agregar una direccion nueva */
    public function store(Request $request)
    {
        $data = $this->validateAddress($request);
        $customer = $this->customer();
        DB::transaction(function() use ($customer, $data){
            // make() desde la relacion llena model type  y model_id automaticamente
            $address = $customer->addresses()->make();
            $this->fillAddress($address, $data, $customer);
            $address->save();
            $this->syncDefault($customer, $address, ! empty($data['is_default']));
        });

        return $this->backToList('Dirección agregada correctamente.');
    }

    /** Editar una edicion existente */
    public function update(Request $request, $id)
    {
        $data = $this->validateAddress($request);
        $customer = $this->customer();
        // Solo editar direcciones que le pertenecen
        $address = $customer->addresses()->findOrFail($id);
        DB::transaction(function() use ($customer, $address, $data) {
            $this->fillAddress($address, $data, $customer);
            $address->save();
            $this->syncDefault($customer, $address, ! empty($data['is_default']));
        });

        return $this->backToList('Dirección actualizada correctamente');
    }

    /** Eliminar una direccion */
    public function destroy($id)
    {
        $customer = $this->customer();
        $address = $customer->addresses()->findOrFail($id);

        DB::transaction(function () use ($customer, $address) {
            //Si era predeterminada, se pasa a otra del mismo tipo (si existe)
            $default = ['default_billing_address_id' => 'billing',
                'default_shipping_address_id' => 'shipping', ];

                foreach ($default as $field => $type)
                {
                    if($customer->$field == $address->id) {
                        $customer->$field = $customer->addresses()
                            ->where('id', '!=', $address->id)
                            ->where('type', $type)
                            ->value('id');
                    }
                }

                $customer->save();
                $address->delete();
        });

        return $this->backToList('Dirección eliminada.');
    }

    /** Marcar una direccion como predeterminada de su tipo */

    public function makeDefault($id){
        $customer = $this->customer();
        $address = $customer->addresses()->findOrFail($id);
        $this->syncDefault($customer, $address, true);

        return $this->backToList('Dirección predeterminada actualizada');
    }

    /** Auxiliares */
    private function validateAddress(Request $request): array {
        return $request->validate([
            'type' => ['required', Rule::in(['shipping', 'billing'])],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'tax_nr' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postalcode' => ['nullable', 'string', 'max:20'],
            'country_id' => ['required', 'exists:countries,id'],
            'province_id' => ['nullable', 'required_if:country_id,GT', Rule::exists('provinces', 'id')->where('country_id', $request->input('country_id'))],
            'is_default' => ['nullable'],
            ],[
            'name.required' => 'Indica el nombre de quien recibe.',
            'address.required' => 'La dirección es obligatoria.',
            'city.required' => 'La ciudad o municipio es obligatoria',
            'country_id.required' => 'Selecciona el pais.',
            'country_id.exists' => 'El país seleccionado no es válido.',
            'province_id.required_if' => 'Selecciona el departamento.',
            'province_id.exists' => 'El departamento no corresponde al país.',
        ]);
    }

    private function fillAddress($address, array $data, $customer):void {
        $parts = preg_split('/\s+/', trim($data['name']), 2);
 
        $address->type       = AddressTypeProxy::create($data['type']);
        $address->name       = $data['name'];
        $address->firstname  = $parts[0];
        $address->lastname   = $parts[1] ?? '';
        $address->email      = $customer->email;
        $address->phone      = $data['phone'] ?? null;
        $address->tax_nr     = $data['tax_nr'] ?? null;
        $address->address    = $data['address'];
        $address->address2   = $data['address2'] ?? null;
        $address->city       = $data['city'];
        $address->postalcode = $data['postalcode'] ?? null;
        $address->country_id = $data['country_id'];
        $address->province_id = $data['province_id'] ?? null;
    }

    /** Actualiza las direcciones privadas del cliente  */

    private function syncDefault($customer, $address, bool $makeDefault): void {
        $type  = $address->type->value();
        $field = $type === 'shipping' ? 'default_shipping_address_id' : 'default_billing_address_id';
        $other = $type === 'shipping' ? 'default_billing_address_id' : 'default_shipping_address_id';
 
        if ($customer->$other == $address->id) {
            $customer->$other = null;
        }
 
        if ($makeDefault || ! $customer->$field) {
            $customer->$field = $address->id;
        }
 
        $customer->save();
    }

    /** Cliente del usuario conectado. Si existe, lo busca por correo
     *  o lo crea, y lo deja ligado al usuario.
     * 
     */

    private function customer(){
        $user = Auth::user();
 
        $customer = $user->customer_id ? CustomerProxy::find($user->customer_id) : null;
 
        if (! $customer) {
            $customer = CustomerProxy::where('email', $user->email)->first();
        }
 
        if (! $customer) {
            $parts    = preg_split('/\s+/', trim($user->name), 2);
            $customer = CustomerProxy::create([
                'type'      => CustomerTypeProxy::create('individual'),
                'email'     => $user->email,
                'firstname' => $parts[0],
                'lastname'  => $parts[1] ?? '',
            ]);
        }
 
        if ($user->customer_id != $customer->id) {
            $user->customer_id = $customer->id;
            $user->save();
        }
 
        return $customer;
    }

    private function backToList(string $message){
        return redirect()
            ->route('home', ['section' => 'addresses'])
            ->with('success', $message);
    }
}
