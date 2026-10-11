<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

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
        ]);
    }

    /** Actualiza los datos del cliente */
    public function updateProfile(Request $request){
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ],[
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo tiene un formato no valido',
            'email.unique' => 'Ese correo ya esta registrado por otro usuario.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return redirect()
            ->route('home', ['section' => 'profile'])
            ->with('success', 'Tus datos se encuentran actualizados correctamente.');
    }
}
