<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Konekt\Customer\Models\CustomerProxy;
use Konekt\Customer\Models\CustomerTypeProxy;

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
        
        return view('home', [
            'user' => $user,
            'orders' => $orders,
            'customer' =>$this->findCustomer($user),
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
        ],[
            'firstname.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo tiene un formato no valido',
            'email.unique' => 'Ese correo ya esta registrado por otro usuario.',
        ]);

        DB::transaction(function()use($user, $data) {
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
            $user->name = trim($data['firstname'].''.($data['lastname'] ?? ''));
            $user->email = $data['email'];
            $user->customer_id = $customer->id;
            $user->save();
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
