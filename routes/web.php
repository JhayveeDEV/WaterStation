<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\DeliveryAreaController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\ReceiptController;
use App\Http\Controllers\Customer\RecurringOrderController;
use App\Http\Controllers\Customer\ShopController;
use App\Http\Controllers\Manage\DashboardController as ManageDashboard;
use App\Http\Controllers\Manage\Inventory\ProductController;
use App\Http\Controllers\Manage\Inventory\StockController;
use App\Http\Controllers\Manage\Orders\OrderActionController;
use App\Http\Controllers\Manage\Orders\OrderBoardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentMockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Rider\DashboardController as RiderDashboard;
use App\Http\Controllers\Rider\DeliveryController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()->getRoleNameAttribute();

        if (! in_array($role, ['admin', 'staff', 'rider', 'customer'], true)) {
            Auth::logout();

            return redirect()->route('login');
        }

        return redirect()->intended(match ($role) {
            'admin' => route('admin.dashboard'),
            'staff' => route('manage.dashboard'),
            'rider' => route('rider.dashboard'),
            'customer' => route('customer.shop'),
            default => route('login'),
        });
    }

    return view('auth.login');
});

// ------------------------------------------------------------------ guest
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1');
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
    Route::get('reset-password', [ResetPasswordController::class, 'create'])->name('password.reset.verify');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ------------------------------------------------------------ shared (any signed-in, active user)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// ------------------------------------------------------------------ customer
Route::middleware(['auth', 'active', 'role:customer'])->prefix('shop')->name('customer.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->middleware('permission:view shop')->name('shop');

    Route::middleware('permission:place orders')->group(function () {
        Route::get('cart', [CartController::class, 'index'])->name('cart');
        Route::post('cart/{product}', [CartController::class, 'add'])->name('cart.add');
        Route::put('cart/{product}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('checkout', [CheckoutController::class, 'create'])->name('checkout');
        Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    });

    Route::middleware('permission:manage own orders')->group(function () {
        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::get('orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/reorder', [CustomerOrderController::class, 'reorder'])
            ->middleware('permission:place orders')->name('orders.reorder');
        Route::get('orders/{order}/receipt', [ReceiptController::class, 'show'])->name('orders.receipt');
        Route::get('orders/{order}/pay', [PaymentController::class, 'initiate'])->name('payments.initiate');
        Route::get('recurring-orders', [RecurringOrderController::class, 'index'])->name('recurring.index');
        Route::patch('recurring-orders/{recurringOrder}', [RecurringOrderController::class, 'toggle'])->name('recurring.toggle');
        Route::post('recurring-orders/{recurringOrder}/skip', [RecurringOrderController::class, 'skip'])->name('recurring.skip');
        Route::post('orders/{order}/recurring', [RecurringOrderController::class, 'store'])
            ->middleware('permission:place orders')->name('orders.recurring.store');
    });
});

// ------------------------------------------------------------------ staff + admin shared workspace
Route::middleware(['auth', 'active', 'role:staff|admin'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('dashboard', [ManageDashboard::class, 'index'])->name('dashboard');

    Route::get('orders', [OrderBoardController::class, 'index'])->name('orders.board');
    Route::get('orders/history', [OrderBoardController::class, 'history'])->name('orders.history');
    Route::get('orders/{order}', [OrderActionController::class, 'show'])->name('orders.show');

    // These two actions are gated by permission, not just role, so admin can
    // grant/revoke them per role from Admin > Roles & permissions.
    Route::middleware('permission:process orders')->group(function () {
        Route::post('orders/{order}/transition', [OrderActionController::class, 'transition'])->name('orders.transition');
    });
    Route::middleware('permission:assign riders')->group(function () {
        Route::post('orders/{order}/assign-rider', [OrderActionController::class, 'assignRider'])->name('orders.assign-rider');
    });

    Route::get('inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}/movements', [StockController::class, 'movements'])->name('inventory.movements');

    Route::middleware('permission:manage inventory')->group(function () {
        Route::get('inventory/create', [ProductController::class, 'create'])->name('inventory.create');
        Route::post('inventory', [ProductController::class, 'store'])->name('inventory.store');
        Route::get('inventory/{product}/edit', [ProductController::class, 'edit'])->name('inventory.edit');
        Route::put('inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{product}', [ProductController::class, 'destroy'])->name('inventory.destroy');
        Route::post('inventory/{product}/stock', [StockController::class, 'store'])->name('inventory.stock');
    });

    Route::middleware('permission:view reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('sales/export', [ReportController::class, 'salesExport'])->name('sales.export');
        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
    });
});

// ------------------------------------------------------------------ rider
Route::middleware(['auth', 'active', 'role:rider'])->prefix('rider')->name('rider.')->group(function () {
    Route::get('dashboard', [RiderDashboard::class, 'index'])->name('dashboard');
    Route::post('availability', [RiderDashboard::class, 'toggleAvailability'])->name('availability');

    Route::middleware('permission:view deliveries')->group(function () {
        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{order}', [DeliveryController::class, 'show'])->name('deliveries.show');
    });

    Route::middleware('permission:update deliveries')->group(function () {
        Route::post('deliveries/{order}/dispatch', [DeliveryController::class, 'dispatch'])->name('deliveries.dispatch');
        Route::post('deliveries/{order}/complete', [DeliveryController::class, 'complete'])->name('deliveries.complete');
        Route::post('deliveries/{order}/location', [DeliveryController::class, 'updateLocation'])->name('deliveries.location');
    });
});

// ------------------------------------------------------------------ payments (sandbox GCash checkout)
Route::middleware(['auth', 'active', 'permission:manage own orders', 'throttle:20,1'])->prefix('payments/mock')->name('payments.mock.')->group(function () {
    Route::get('{payment}', [PaymentMockController::class, 'show'])->name('show');
    Route::post('{payment}/complete', [PaymentMockController::class, 'complete'])->name('complete');
    Route::post('{payment}/cancel', [PaymentMockController::class, 'cancel'])->name('cancel');
});

// ------------------------------------------------------------------ admin only
Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    Route::middleware('permission:manage users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'destroy'])->name('users.toggle-status');
    });

    Route::middleware('permission:manage settings')->group(function () {
        Route::get('areas', [DeliveryAreaController::class, 'index'])->name('areas.index');
        Route::post('areas', [DeliveryAreaController::class, 'store'])->name('areas.store');
        Route::put('areas/{deliveryArea}', [DeliveryAreaController::class, 'update'])->name('areas.update');
        Route::delete('areas/{deliveryArea}', [DeliveryAreaController::class, 'destroy'])->name('areas.destroy');
    });

    Route::middleware('permission:view reports')->group(function () {
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/sales/export', [ReportController::class, 'salesExport'])->name('reports.sales.export');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    });

    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::middleware('permission:manage users')->group(function () {
        Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
    });

    Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('refunds/{payment}/mark-refunded', [RefundController::class, 'markRefunded'])->name('refunds.mark-refunded');
});
