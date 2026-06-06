<?php

use App\Http\Controllers\Web\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Web\Admin\ShippingController as AdminShippingController;
use App\Http\Controllers\Web\CartWebController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\CustomerAuthController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalogo', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/quienes-somos', [ShopController::class, 'about'])->name('about');
Route::get('/producto/{slug}', [ShopController::class, 'show'])->name('product.show');

Route::get('/carro', [CartWebController::class, 'index'])->name('cart.index');
Route::post('/carro/agregar', [CartWebController::class, 'add'])->name('cart.add');
Route::patch('/carro/{id}', [CartWebController::class, 'update'])->name('cart.update');
Route::delete('/carro/{id}', [CartWebController::class, 'remove'])->name('cart.remove');

Route::get('/cuenta/ingresar', [CustomerAuthController::class, 'showLogin'])->name('account.login');
Route::post('/cuenta/ingresar', [CustomerAuthController::class, 'login'])->name('account.login.store');
Route::post('/cuenta/salir', [CustomerAuthController::class, 'logout'])->name('account.logout')->middleware('auth');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::get('/checkout/envio', [CheckoutController::class, 'quote'])->name('checkout.shipping.quote');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::match(['get', 'post'], '/checkout/webpay/return', [PaymentWebController::class, 'return'])
    ->name('checkout.webpay.return');
Route::get('/checkout/webpay/retry/{uuid}', [PaymentWebController::class, 'retry'])
    ->name('checkout.webpay.retry');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('cuenta/clave', [AdminAccountController::class, 'editPassword'])->name('account.password');
        Route::put('cuenta/clave', [AdminAccountController::class, 'updatePassword'])->name('account.password.update');
        Route::redirect('/', '/admin/productos');

        Route::get('productos', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('productos/carga-masiva', [AdminProductController::class, 'importForm'])->name('products.import');
        Route::get('productos/carga-masiva/plantilla', [AdminProductController::class, 'downloadImportTemplate'])->name('products.import.template');
        Route::get('productos/exportar', [AdminProductController::class, 'exportProducts'])->name('products.export');
        Route::post('productos/carga-masiva/chunk', [AdminProductController::class, 'storeImportChunk'])->name('products.import.chunk');
        Route::post('productos/carga-masiva/procesar', [AdminProductController::class, 'processImportBatch'])->name('products.import.process');
        Route::get('productos/nuevo', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('productos', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('productos/{product}/editar', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('productos/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('productos/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('categorias', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('categorias/nueva', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('categorias', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('categorias/{category}/editar', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categorias/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categorias/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('ventas', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('ventas/exportar', [AdminOrderController::class, 'export'])->name('orders.export');
        Route::get('ventas/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

        Route::get('envios', [AdminShippingController::class, 'index'])->name('shipping.index');
        Route::get('envios/carga-masiva', [AdminShippingController::class, 'importForm'])->name('shipping.import');
        Route::get('envios/carga-masiva/plantilla', [AdminShippingController::class, 'downloadImportTemplate'])->name('shipping.import.template');
        Route::get('envios/exportar-tramos', [AdminShippingController::class, 'exportRates'])->name('shipping.export');
        Route::post('envios/carga-masiva', [AdminShippingController::class, 'processImport'])->name('shipping.import.store');
        Route::put('envios', [AdminShippingController::class, 'updateSettings'])->name('shipping.settings');
        Route::put('envios/regiones', [AdminShippingController::class, 'updateRegionRates'])->name('shipping.regions');
        Route::post('envios/tramos', [AdminShippingController::class, 'storeRate'])->name('shipping.rates.store');
        Route::put('envios/tramos/{rate}', [AdminShippingController::class, 'updateRate'])->name('shipping.rates.update');
        Route::delete('envios/tramos/{rate}', [AdminShippingController::class, 'destroyRate'])->name('shipping.rates.destroy');
    });
});
