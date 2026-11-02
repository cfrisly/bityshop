<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vanilo\Product\Models\ProductProxy;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    /** Si el producto no esta en favoritos lo agrega si ya esta lo quita */
    public function toggle(Request $request, $productId){
        $product = ProductProxy::findOrFail($productId);
        $result = Auth::user()->favoriteProducts()->toggle($product->id);
        $isFavorite = count($result['attached']) > 0;

        // Respuesta para el clic sin recargar la pagina
        if($request->expectsJson()){
            return response()->json(['favorite' => $isFavorite]);
        }
        // Respuesta normal si el navegador no uso JavaScript
        return back()->with(
            'success',
            $isFavorite ? 'Producto agregado a favoritos.' : 'Producto quitado de favoritos.'
        );
    }
}
