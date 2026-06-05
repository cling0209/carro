<?php

use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Web\Admin\ShippingController as AdminShippingController;
use App\Http\Controllers\Web\CartWebController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalogo', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/producto/{slug}', [ShopController::class, 'show'])->name('product.show');

Route::get('/carro', [CartWebController::class, 'index'])->name('cart.index');
Route::post('/carro/agregar', [CartWebController::class, 'add'])->name('cart.add');
Route::patch('/carro/{id}', [CartWebController::class, 'update'])->name('cart.update');
Route::delete('/carro/{id}', [CartWebController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::get('/checkout/envio', [CheckoutController::class, 'quote'])->name('checkout.shipping.quote');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::match(['get', 'post'], '/checkout/webpay/return', [PaymentWebController::class, 'return'])
    ->name('checkout.webpay.return');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::redirect('/', '/admin/productos');

        Route::get('productos', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('productos/nuevo', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('productos', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('productos/{product}/editar', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('productos/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('productos/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('ventas', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('ventas/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

        Route::get('envios', [AdminShippingController::class, 'index'])->name('shipping.index');
        Route::put('envios', [AdminShippingController::class, 'updateSettings'])->name('shipping.settings');
        Route::post('envios/tramos', [AdminShippingController::class, 'storeRate'])->name('shipping.rates.store');
        Route::put('envios/tramos/{rate}', [AdminShippingController::class, 'updateRate'])->name('shipping.rates.update');
        Route::delete('envios/tramos/{rate}', [AdminShippingController::class, 'destroyRate'])->name('shipping.rates.destroy');
    });
});
