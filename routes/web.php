<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GuidesController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationManagement\EnterRequestController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\TransportationController;
use App\Http\Controllers\WarehouseManagement\LocationController;
use App\Http\Controllers\WarehouseManagement\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class);
    Route::get('products-search', [ProductController::class, 'search'])->name('products.search');
    Route::resource('products', ProductController::class);

    Route::name('warehouse-management.')
        ->prefix('warehouse-management/')
        ->group(function () {
            Route::resource('warehouses', WarehouseController::class);
            Route::resource('locations', LocationController::class);
            Route::get('/locations-line/{id}/edit', [LocationController::class, 'locationsLine'])->name('line-locations');
            Route::post('/locations-line/{id}/update', [LocationController::class, 'locationsLineUpdate'])->name('line-locations-update');
        });

    Route::name('operation-management.')
        ->prefix('operation-management/')
        ->group(function () {
            Route::resource('enter_requests', EnterRequestController::class);
            Route::post('/enter_requests/{id}/cars/store', [EnterRequestController::class, 'cars'])->name('enter_requests.cars.store');
            Route::delete('enter_requests/files/{id}', [EnterRequestController::class, 'fileDelete'])->name('enter_requests.files.delete');
            Route::get('enter_requests/{id}/pdf', [EnterRequestController::class, 'pdf'])->name('enter_requests.pdf');
        });

    Route::name('user-management.')->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });
});

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';
