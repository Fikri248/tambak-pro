<?php

use App\Http\Controllers\AccountLookupController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CommodityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedingTransactionController;
use App\Http\Controllers\FeedItemController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockingTransactionController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))
    ->name('home');

Route::middleware('guest')->controller(AuthenticatedSessionController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')->name('login.store');
});

Route::middleware('guest')->controller(RegisterController::class)->group(function () {
    Route::get('/register', 'create')->name('register');
    Route::post('/register', 'store')->middleware('throttle:5,1')->name('register.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/akun/password', [PasswordController::class, 'edit'])
        ->name('account.password.edit');
    Route::patch('/akun/password', [PasswordController::class, 'update'])
        ->name('account.password.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('access:dashboard.view')
        ->name('dashboard');

    Route::middleware('access:locations.manage')->controller(LocationController::class)->group(function () {
        Route::get('/tambak/create', 'create')->name('tambak.create');
        Route::post('/tambak', 'store')->name('tambak.store');
        Route::get('/tambak/{location}/edit', 'edit')->name('tambak.edit');
        Route::match(['put', 'patch'], '/tambak/{location}', 'update')->name('tambak.update');
        Route::patch('/tambak/{location}/status', 'status')->name('tambak.status');
    });

    Route::middleware('access:locations.view')->controller(LocationController::class)->group(function () {
        Route::get('/tambak', 'index')->name('tambak.index');
        Route::get('/tambak/{location}', 'show')->name('tambak.show');
    });

    Route::middleware('access:commodities.manage')->controller(CommodityController::class)->group(function () {
        Route::get('/komoditas/create', 'create')->name('commodities.create');
        Route::post('/komoditas', 'store')->name('commodities.store');
        Route::get('/komoditas/{commodity}/edit', 'edit')->name('commodities.edit');
        Route::match(['put', 'patch'], '/komoditas/{commodity}', 'update')->name('commodities.update');
        Route::patch('/komoditas/{commodity}/status', 'status')->name('commodities.status');
    });

    Route::middleware('access:commodities.view')->controller(CommodityController::class)->group(function () {
        Route::get('/komoditas', 'index')->name('commodities.index');
        Route::get('/komoditas/{commodity}', 'show')->name('commodities.show');
    });

    Route::middleware('access:vendors.manage')->controller(VendorController::class)->group(function () {
        Route::get('/vendor/create', 'create')->name('vendors.create');
        Route::post('/vendor', 'store')->name('vendors.store');
        Route::get('/vendor/{vendor}/edit', 'edit')->name('vendors.edit');
        Route::match(['put', 'patch'], '/vendor/{vendor}', 'update')->name('vendors.update');
        Route::patch('/vendor/{vendor}/status', 'status')->name('vendors.status');
    });

    Route::middleware('access:vendors.view')->controller(VendorController::class)->group(function () {
        Route::get('/vendor', 'index')->name('vendors.index');
        Route::get('/vendor/{vendor}', 'show')->name('vendors.show');
    });

    Route::middleware('access:feed-items.manage')->controller(FeedItemController::class)->group(function () {
        Route::get('/pakan/create', 'create')->name('feed-items.create');
        Route::post('/pakan', 'store')->name('feed-items.store');
        Route::get('/pakan/{feedItem}/edit', 'edit')->name('feed-items.edit');
        Route::match(['put', 'patch'], '/pakan/{feedItem}', 'update')->name('feed-items.update');
        Route::patch('/pakan/{feedItem}/status', 'status')->name('feed-items.status');
    });

    Route::middleware('access:feed-items.view')->controller(FeedItemController::class)->group(function () {
        Route::get('/pakan', 'index')->name('feed-items.index');
        Route::get('/pakan/{feedItem}', 'show')->name('feed-items.show');
    });

    Route::middleware('access:chart-of-accounts.manage')->group(function () {
        Route::post('/chart-of-accounts/lookups', [AccountLookupController::class, 'store'])
            ->name('chart-of-accounts.lookups.store');
        Route::patch('/chart-of-accounts/lookups/{lookupType}/{lookup}', [AccountLookupController::class, 'update'])
            ->name('chart-of-accounts.lookups.update');
        Route::delete('/chart-of-accounts/lookups/{lookupType}/{lookup}', [AccountLookupController::class, 'destroy'])
            ->name('chart-of-accounts.lookups.destroy');

        Route::controller(ChartOfAccountController::class)->group(function () {
            Route::get('/chart-of-accounts/create', 'create')->name('chart-of-accounts.create');
            Route::post('/chart-of-accounts', 'store')->name('chart-of-accounts.store');
            Route::get('/chart-of-accounts/{chartOfAccount}/edit', 'edit')->name('chart-of-accounts.edit');
            Route::match(['put', 'patch'], '/chart-of-accounts/{chartOfAccount}', 'update')->name('chart-of-accounts.update');
            Route::patch('/chart-of-accounts/{chartOfAccount}/status', 'status')->name('chart-of-accounts.status');
            Route::delete('/chart-of-accounts/{chartOfAccount}', 'destroy')->name('chart-of-accounts.destroy');
        });
    });

    Route::middleware('access:chart-of-accounts.view')->controller(ChartOfAccountController::class)->group(function () {
        Route::get('/chart-of-accounts', 'index')->name('chart-of-accounts.index');
        Route::get('/chart-of-accounts/{chartOfAccount}', 'show')->name('chart-of-accounts.show');
    });

    Route::middleware('access:stocking.create')->controller(StockingTransactionController::class)->group(function () {
        Route::get('/pembibitan/create', 'create')->name('stocking.create');
        Route::post('/pembibitan', 'store')->name('stocking.store');
    });

    Route::middleware('access:stocking.update')->controller(StockingTransactionController::class)->group(function () {
        Route::get('/pembibitan/{stockingTransaction}/edit', 'edit')->name('stocking.edit');
        Route::match(['put', 'patch'], '/pembibitan/{stockingTransaction}', 'update')->name('stocking.update');
    });

    Route::delete('/pembibitan/{stockingTransaction}', [StockingTransactionController::class, 'destroy'])
        ->middleware('access:stocking.delete')
        ->name('stocking.destroy');

    Route::middleware('access:stocking.view')->controller(StockingTransactionController::class)->group(function () {
        Route::get('/pembibitan', 'index')->name('stocking.index');
        Route::get('/pembibitan/{stockingTransaction}', 'show')->name('stocking.show');
    });

    Route::middleware('access:movements.create')->controller(StockMovementController::class)->group(function () {
        Route::get('/mutasi/create', 'create')->name('movements.create');
        Route::post('/mutasi', 'store')->name('movements.store');
    });

    Route::middleware('access:movements.update')->controller(StockMovementController::class)->group(function () {
        Route::get('/mutasi/{stockMovement}/edit', 'edit')->name('movements.edit');
        Route::match(['put', 'patch'], '/mutasi/{stockMovement}', 'update')->name('movements.update');
    });

    Route::delete('/mutasi/{stockMovement}', [StockMovementController::class, 'destroy'])
        ->middleware('access:movements.delete')
        ->name('movements.destroy');

    Route::middleware('access:movements.view')->controller(StockMovementController::class)->group(function () {
        Route::get('/mutasi', 'index')->name('movements.index');
        Route::get('/mutasi/{stockMovement}', 'show')->name('movements.show');
    });

    Route::middleware('access:adjustments.create')->controller(StockAdjustmentController::class)->group(function () {
        Route::get('/perubahan-jumlah/create', 'create')->name('adjustments.create');
        Route::post('/perubahan-jumlah', 'store')->name('adjustments.store');
    });

    Route::middleware('access:adjustments.update')->controller(StockAdjustmentController::class)->group(function () {
        Route::get('/perubahan-jumlah/{stockAdjustment}/edit', 'edit')->name('adjustments.edit');
        Route::match(['put', 'patch'], '/perubahan-jumlah/{stockAdjustment}', 'update')->name('adjustments.update');
    });

    Route::delete('/perubahan-jumlah/{stockAdjustment}', [StockAdjustmentController::class, 'destroy'])
        ->middleware('access:adjustments.delete')
        ->name('adjustments.destroy');

    Route::middleware('access:adjustments.view')->controller(StockAdjustmentController::class)->group(function () {
        Route::get('/perubahan-jumlah', 'index')->name('adjustments.index');
        Route::get('/perubahan-jumlah/{stockAdjustment}', 'show')->name('adjustments.show');
    });

    Route::middleware('access:feeding.create')->controller(FeedingTransactionController::class)->group(function () {
        Route::get('/pemberian-pakan/create', 'create')->name('feeding.create');
        Route::post('/pemberian-pakan', 'store')->name('feeding.store');
    });

    Route::middleware('access:feeding.update')->controller(FeedingTransactionController::class)->group(function () {
        Route::get('/pemberian-pakan/{feedingTransaction}/edit', 'edit')->name('feeding.edit');
        Route::match(['put', 'patch'], '/pemberian-pakan/{feedingTransaction}', 'update')->name('feeding.update');
    });

    Route::delete('/pemberian-pakan/{feedingTransaction}', [FeedingTransactionController::class, 'destroy'])
        ->middleware('access:feeding.delete')
        ->name('feeding.destroy');

    Route::middleware('access:feeding.view')->controller(FeedingTransactionController::class)->group(function () {
        Route::get('/pemberian-pakan', 'index')->name('feeding.index');
        Route::get('/pemberian-pakan/{feedingTransaction}', 'show')->name('feeding.show');
    });

    Route::get('/riwayat-transaksi', [TransactionHistoryController::class, 'index'])
        ->middleware('access:history.view')
        ->name('history.index');

    Route::prefix('laporan')->name('reports.')->middleware('access:reports.view')->controller(ReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        foreach ([
            'stok' => 'stock',
            'pembibitan' => 'stocking',
            'mutasi' => 'movements',
            'perubahan-jumlah' => 'adjustments',
            'pakan' => 'feeding',
            'vendor' => 'vendors',
            'komoditas' => 'commodities',
            'tambak' => 'locations',
        ] as $uri => $report) {
            Route::get("/{$uri}/print", 'printReport')->defaults('report', $report)->name("{$report}.print");
            Route::get("/{$uri}/pdf", 'pdf')->defaults('report', $report)->name("{$report}.pdf");
        }
        Route::get('/stok/export', 'exportStock')->name('stock.export');
        Route::get('/pembibitan/export', 'exportStocking')->name('stocking.export');
        Route::get('/mutasi/export', 'exportMovements')->name('movements.export');
        Route::get('/perubahan-jumlah/export', 'exportAdjustments')->name('adjustments.export');
        Route::get('/pakan/export', 'exportFeeding')->name('feeding.export');
        Route::get('/vendor/export', 'exportVendors')->name('vendors.export');
        Route::get('/komoditas/export', 'exportCommodities')->name('commodities.export');
        Route::get('/tambak/export', 'exportLocations')->name('locations.export');
        Route::get('/stok', 'stock')->name('stock');
        Route::get('/pembibitan', 'stocking')->name('stocking');
        Route::get('/mutasi', 'movements')->name('movements');
        Route::get('/perubahan-jumlah', 'adjustments')->name('adjustments');
        Route::get('/pakan', 'feeding')->name('feeding');
        Route::get('/vendor', 'vendors')->name('vendors');
        Route::get('/komoditas', 'commodities')->name('commodities');
        Route::get('/tambak', 'locations')->name('locations');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
