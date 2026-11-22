<?php
use App\Http\Controllers\OrderController;

Route::get('/', 'ProductController@index');

Auth::routes();

Route::post('/direcciones', 'AddressController@store')->name('addresses.store');
Route::put('/direcciones/{id}', 'AddressController@update')->name('addresses.update');
Route::delete('/direcciones/{id}', 'AddressController@destroy')->name('addresses.destroy');
Route::post('/direcciones/{id}/predeterminada', 'AddressController@makeDefault')->name('addresses.default');
Route::post('/favoritos/{id}', 'FavoriteController@toggle')->name('favorites.toggle');

Route::post('/orders/{id}/cancel', [
   OrderController::class,
   'cancel'
])->name('orders.cancel');

Route::get('/search', 'ProductController@search')->name('product.search');

Route::get('/home', 'HomeController@index')->name('home');
Route::put('/perfil', 'HomeController@updateProfile')->name('profile.update');

Route::group(['prefix' => 'shop', 'as' => 'product.'], function() {
   Route::get('index', 'ProductController@index')->name('index');
   Route::get('ofertas', 'ProductController@ofertas')->name('ofertas');
   Route::get('nuevo', 'ProductController@nuevo')->name('nuevo');
   Route::get('c/{taxonomyName}/{taxon}', 'ProductController@index')->name('category');
   Route::get('p/{slug}', 'ProductController@show')->name('show');
});

Route::group(['prefix' => 'cart', 'as' => 'cart.'], function() {
   Route::get('show', 'CartController@show')->name('show');
   Route::post('add/{product}', 'CartController@add')->name('add');
   Route::post('adv/{masterProductVariant}', 'CartController@addVariant')->name('add-variant');
   Route::post('update/{cart_item}', 'CartController@update')->name('update');
   Route::post('remove/{cart_item}', 'CartController@remove')->name('remove');
   Route::post('favorito/{cart_item}', 'CartController@moveToFavorites')->middleware('auth')->name('favorite');
   Route::post('cupon', 'CartController@applyCoupon')->name('coupon.apply');
   Route::post('regalo', 'CartController@toggleGift')->name('gift');
   Route::post('cupon/{code}/quitar', 'CartController@removeCoupon')->name('coupon.remove');
});

Route::group(['prefix' => 'checkout', 'as' => 'checkout.'], function() {
   Route::get('show', 'CheckoutController@show')->name('show');
   Route::post('submit', 'CheckoutController@submit')->name('submit');
});

Route::group(['prefix' => 'payment/eup', 'as' => 'payment.euplatesc.return.'], function() {
   Route::post('frontend', 'EuplatescReturnController@frontend')->name('frontend');
   Route::post('silent', 'EuplatescReturnController@silent')->name('silent');
});

Route::group(['prefix' => 'payment/netopia', 'as' => 'payment.netopia.'], function() {
   Route::post('confirm', 'NetopiaReturnController@confirm')->name('confirm');
   Route::get('return', 'NetopiaReturnController@return')->name('return');
});

Route::group(['prefix' => 'payment/paypal', 'as' => 'payment.paypal.'], function() {
   Route::get('return', 'PaypalReturnController@return')->name('return');
   Route::get('cancel', 'PaypalReturnController@cancel')->name('cancel');
   Route::any('webhook', 'PaypalReturnController@webhook')->name('webhook');
});


Route::group(['prefix' => 'payment/mollie', 'as' => 'payment.mollie.'], function() {
   Route::get('{paymentId}/return', 'MollieController@return')->name('return');
   Route::post('webhook', 'MollieController@webhook')->name('webhook');
});

Route::group(['prefix' => 'payment/adyen', 'as' => 'payment.adyen.'], function() {
   Route::post('{paymentId}/submit', 'AdyenController@submit')->name('submit');
   Route::post('webhook', 'AdyenController@webhook')->name('webhook');
});

Route::group(['prefix' => 'payment/braintree', 'as' => 'payment.braintree.'], function() {
   Route::post('{paymentId}/submit', 'BraintreeController@submit')->name('submit');
});

// Admin de la tienda (usa el panel de AppShell y sus permisos)
Route::group(['prefix' => 'admin', 'as' => 'tienda.admin.', 'middleware' => ['auth', 'acl']], function () {
   Route::resource('logo', \App\Http\Controllers\Admin\LogoController::class)->except('show');
});
