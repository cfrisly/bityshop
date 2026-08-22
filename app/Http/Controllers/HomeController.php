<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

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
            ->latest()
            ->toke(5)
            ->get();
        
        return view('home', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }
}
