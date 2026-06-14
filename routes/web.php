<?php

use App\Http\Controllers\Web\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\PasswordResetController as AdminPasswordResetController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Web\Admin\ShippingController as AdminShippingController;
use App\Http\Controllers\Web\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\CartWebController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\CustomerAuthController;
use App\Http\Controllers\Web\CustomerPasswordResetController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalogo', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/quienes-somos', [ShopController::class, 'about'])->name('about');
Route::get('/producto/{slug}', [ShopController::class, 'show'])->name('product.show');

Route::get('/carro', [CartWebController::class, 'index'])->name('cart.index');
Route::post('/carro/agregar', [CartWebController::class, 'add'])->name('cart.add');
Route::post('/carro/ir-a-pagar', [CartWebController::class, 'sync'])->name('cart.sync');
Route::patch('/carro/{id}', [CartWebController::class, 'update'])->name('cart.update');
Route::delete('/carro/{id}', [CartWebController::class, 'remove'])->name('cart.remove');

Route::get('/cuenta/ingresar', [CustomerAuthController::class, 'showLogin'])->name('account.login');
Route::post('/cuenta/ingresar', [CustomerAuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('account.login.store');
Route::get('/cuenta/password/olvidada', [CustomerPasswordResetController::class, 'create'])->name('account.password.request');
Route::post('/cuenta/password/olvidada', [CustomerPasswordResetController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('account.password.email');
Route::get('/cuenta/password/restablecer/{token}', [CustomerPasswordResetController::class, 'edit'])->name('account.password.reset');
Route::post('/cuenta/password/restablecer', [CustomerPasswordResetController::class, 'update'])
    ->middleware('throttle:5,1')
    ->name('account.password.update');
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
    Route::post('login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('login/verificar', [AdminAuthController::class, 'showVerify'])->name('login.verify');
    Route::post('login/verificar', [AdminAuthController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('login.verify.store');

    Route::get('password/olvidada', [AdminPasswordResetController::class, 'create'])->name('password.request');
    Route::post('password/olvidada', [AdminPasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('password/restablecer', [AdminPasswordResetController::class, 'edit'])->name('password.reset');
    Route::get('password/restablecer/{token}', [AdminPasswordResetController::class, 'edit'])->name('password.reset.link');
    Route::post('password/restablecer', [AdminPasswordResetController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('cuenta/clave', [AdminAccountController::class, 'editPassword'])->name('account.password');
        Route::put('cuenta/clave', [AdminAccountController::class, 'updatePassword'])->name('account.password.update');

        Route::get('usuarios', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('usuarios/nuevo', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('usuarios', [AdminUserController::class, 'store'])->name('users.store');
        Route::delete('usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('clientes', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::delete('clientes/{user}', [AdminCustomerController::class, 'destroy'])->name('customers.destroy');

        Route::redirect('/', '/admin/productos');

        Route::get('productos', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('productos/carga-masiva', [AdminProductController::class, 'importForm'])->name('products.import');
        Route::get('productos/carga-masiva/estado', [AdminProductController::class, 'importStatus'])->name('products.import.status');
        Route::post('productos/carga-masiva/liberar', [AdminProductController::class, 'releaseImportLock'])->name('products.import.unlock');
        Route::get('productos/carga-masiva/resultado/{run}', [AdminProductController::class, 'importResult'])->name('products.import.resultado')->whereNumber('run');
        Route::get('productos/carga-masiva/errores/{run}', [AdminProductController::class, 'importErrors'])->name('products.import.errores')->whereNumber('run');
        Route::get('productos/carga-masiva/errores/{run}/exportar', [AdminProductController::class, 'exportImportErrors'])->name('products.import.errores.exportar')->whereNumber('run');
        Route::get('productos/carga-masiva/plantilla', [AdminProductController::class, 'downloadImportTemplate'])->name('products.import.template');
        Route::get('productos/carga-masiva/plantilla-excel', [AdminProductController::class, 'downloadImportTemplateExcel'])->name('products.import.template.excel');
        Route::post('productos/carga-masiva/chunk', [AdminProductController::class, 'storeImportChunk'])->name('products.import.chunk');
        Route::post('productos/carga-masiva/inicializar', [AdminProductController::class, 'initializeCustomImport'])->name('products.import.initialize');
        Route::post('productos/carga-masiva/vista-previa', [AdminProductController::class, 'previewImportMapping'])->name('products.import.preview');
        Route::post('productos/carga-masiva/preparar-plantilla', [AdminProductController::class, 'prepareTemplateImport'])->name('products.import.prepare.template');
        Route::post('productos/carga-masiva/preparar', [AdminProductController::class, 'prepareCustomImport'])->name('products.import.prepare');
        Route::post('productos/carga-masiva/procesar', [AdminProductController::class, 'processImportBatch'])->name('products.import.process');
        Route::post('productos/carga-masiva/procesar-background', [AdminProductController::class, 'startBackgroundImport'])->name('products.import.background');
        Route::get('productos/carga-masiva/progreso', [AdminProductController::class, 'importProgress'])->name('products.import.progress');
        Route::get('productos/exportar', [AdminProductController::class, 'exportProducts'])->name('products.export');
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
        Route::post('envios/carga-masiva/chunk', [AdminShippingController::class, 'storeImportChunk'])->name('shipping.import.chunk');
        Route::post('envios/carga-masiva/procesar', [AdminShippingController::class, 'processImportBatch'])->name('shipping.import.process');
        Route::put('envios', [AdminShippingController::class, 'updateSettings'])->name('shipping.settings');
        Route::put('envios/regiones', [AdminShippingController::class, 'updateRegionRates'])->name('shipping.regions');
        Route::post('envios/tramos', [AdminShippingController::class, 'storeRate'])->name('shipping.rates.store');
        Route::put('envios/tramos/{rate}', [AdminShippingController::class, 'updateRate'])->name('shipping.rates.update');
        Route::delete('envios/tramos/{rate}', [AdminShippingController::class, 'destroyRate'])->name('shipping.rates.destroy');
    });
});
