<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Konekt\Customer\Models\CustomerProxy;
use Konekt\Customer\Models\CustomerTypeProxy;
use Konekt\Address\Models\AddressProxy;
use Konekt\Address\Models\AddressTypeProxy;
use Konekt\Address\Models\CountryProxy;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        $orders = $user->orders()
            //->with('items.products')
            ->latest()
            ->take(5)
            ->get();

            $customer = $this->findCustomer($user);

            $address = ($customer && $customer->default_billing_address_id)
            ? AddressProxy::find($customer->default_billing_address_id)
            : null;
        
        return view('home', [
            'user' => $user,
            'orders' => $orders,
            'customer' =>$customer,
            'address' => $address,
            'addresses' => $customer ? $customer->addresses()->latest()->get() : collect(),
            'countries' => CountryProxy::orderBy('name')->get(),
            'favorites' => $user->favoriteProducts()->orderBy('favorites.created_at', 'desc')->get(),
        ]);
    }

    /** Actualiza los datos del cliente */
    public function updateProfile(Request $request){
        $user = Auth::user();

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', Rule::in(['individual', 'organization'])],
            'company_name' => ['nullable', 'required_if:type, organization', 'string', 'max:255'],
            'tax_nr' => ['nullable', 'string', 'max:30'],
            // Direccion
            'address'      => ['nullable', 'string', 'max:255'],
            'address2'     => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'required_with:address', 'string', 'max:100'],
            'postalcode'   => ['nullable', 'string', 'max:20'],
            'country_id'   => ['nullable', 'required_with:address', 'exists:countries,id'],
        ],[
            'firstname.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo tiene un formato no valido',
            'email.unique' => 'Ese correo ya esta registrado por otro usuario.',
            'company_name.required_if' => 'Si eres empresa, indica el nombre de la empresa.',
            'city.required_with'       => 'Si ingresas una dirección, indica también la ciudad.',
            'country_id.required_with' => 'Si ingresas una dirección, selecciona el país.',
            'country_id.exists'        => 'El país seleccionado no es válido.',
        ]);

        DB::transaction(function()use($user, $data) {

            $fullName = trim($data['firstname'].' '.($data['lastname'] ?? ''));
            // 1. Cliente: el existente o uno nuevo
            $customer = $this->findCustomer($user) ?? CustomerProxy::make([]);

            $customer->type = CustomerTypeProxy::create($data['type']);
            $customer->firstname = $data['firstname'];
            $customer->lastname = $data['lastname'] ?? '';
            $customer->email = $data['email'];
            $customer->phone = $data['phone'] ?? null;
            $customer->company_name = $data['type'] === 'organization' ? $data['company_name'] : null;
            $customer->tax_nr = $data['tax_nr'] ?? null;
            $customer->save();

            // 2. Usuario: nombre completo, correo y vinculo con el cliente
            $user->name = $fullName;
            $user->email = $data['email'];
            $user->customer_id = $customer->id;
            $user->save();

            // 3. Direccion (Solo si escribio una)
            if (! empty($data['address'])) {

                $address = $customer->default_billing_address_id
                    ? AddressProxy::find($customer->default_billing_address_id)
                    : null;
                
                if (! $address) {
                    $address = AddressProxy::make([]);
                    $address->type = AddressTypeProxy::create('billing');
                }

                $address->name         = $fullName;
                $address->firstname    = $data['firstname'];
                $address->lastname     = $data['lastname'] ?? '';
                $address->email        = $data['email'];
                $address->phone        = $data['phone'] ?? null;
                $address->company_name = $customer->company_name;
                $address->tax_nr       = $data['tax_nr'] ?? null;
                $address->address      = $data['address'];
                $address->address2     = $data['address2'] ?? null;
                $address->city         = $data['city'];
                $address->postalcode   = $data['postalcode'] ?? null;
                $address->country_id   = $data['country_id'];
                // Ligar la dirección al cliente
                $address->model_type   = $customer->getMorphClass();
                $address->model_id     = $customer->id;
                $address->save();

                $customer->default_billing_address_id = $address->id;
                $customer->save();
                
            }
        });

        return redirect()
            ->route('home', ['section' => 'profile'])
            ->with('success', 'Tus datos se encuentran actualizados correctamente.');
    }

    /** Cliente ligado al usuario; si no tiene, busca uno con su mismo correo. */
    private function findCustomer($user){
        if ($user->customer_id) {
            $customer = CustomerProxy::find($user->customer_id);
            if ($customer){
                return $customer;
            }
        }

        return CustomerProxy::where('email', $user->email)->first();
    }
}
