<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AsyncRequestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BackendController;
use App\Http\Controllers\BajiController;
use App\Http\Controllers\Bj88Controller;
use App\Http\Controllers\JeetbuzzController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Six6SController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;







Route::get('/', [AuthenticatedSessionController::class, 'create']);

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::group(['middleware' => ['auth', 'verified']], function () {

    // Admin Routes
    Route::group(['prefix' => 'admin', 'middleware' => ['role:admin'], 'as' => 'admin.'], function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/request', [AdminController::class, 'requestAccount'])->name('request.account');
        Route::get('/granted', [AdminController::class, 'grantedAccount'])->name('granted.account');
        Route::put('/account-granted/{id}', [AdminController::class, 'setAccountAccess'])->name('granted.account.set');
        Route::put('/account-decline/{id}', [AdminController::class, 'setAccountRemoveAccess'])->name('decline.account.set');

        // Baji Contoller
        Route::get('/baji', [BajiController::class, 'index'])->name('baji');
        // Route::post('/baji/currency', [BajiController::class, 'createCurrency'])->name('baji.create.currency');

        //send test rquest to backend
        Route::post('/bdt-bo',[BackendController::class, 'BdtBOFetcher'])->name('bo');
        //send to spreedsheet
        Route::post('/spreedsheet',[BackendController::class, 'Spreedsheet'])->name('spreedsheet');
        //async request
        Route::get('/total-request', [AsyncRequestController::class, 'totalRequestAccount'])->name('request.account.count');

        //bj88
        Route::get('/bj88', [Bj88Controller::class, 'index'])->name('bj88');
        //send request to fetch BO for bj88
        Route::post('bj88/bo',[Bj88Controller::class, 'bj88BO'])->name('bj88.bo');

        //six6s
        Route::get('/six6s', [Six6SController::class, 'index'])->name('six6s');
         //send request to fetch BO for bj88
        Route::post('six6s/bo',[Six6SController::class, 'six6sBO'])->name('six6s.bo');

        //jeetbuzz
        Route::get('/jeetbuzz', [JeetbuzzController::class, 'index'])->name('jeetbuzz');
         //send request to fetch BO for jeetbuzz
        Route::post('jeetbuzz/bo',[JeetbuzzController::class, 'jeetbuzzBO'])->name('jeetbuzz.bo');
    });

    // User Routes
    Route::group(['prefix' => 'user', 'middleware' => ['role:user'], 'as' => 'user.'], function () {
        Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');
        // Baji Contoller
        Route::get('/baji', [BajiController::class, 'index'])->name('baji');
        Route::post('/baji/currency', [BajiController::class, 'createCurrency'])->name('baji.create.currency');
    });

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
