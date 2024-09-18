<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AsyncRequestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BackendController;
use App\Http\Controllers\BajiController;
use App\Http\Controllers\Bj88Controller;
use App\Http\Controllers\CtnController;
use App\Http\Controllers\Ic88Controller;
use App\Http\Controllers\JeetbuzzController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Six6SController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WinrsController;
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
        //send to spreedsheet
        Route::post('bj88/spreedsheet',[Bj88Controller::class, 'Spreedsheet'])->name('bj88.spreedsheet');

        //six6s
        Route::get('/six6s', [Six6SController::class, 'index'])->name('six6s');
         //send request to fetch BO for bj88
        Route::post('six6s/bo',[Six6SController::class, 'six6sBO'])->name('six6s.bo');
        //send to spreedsheet
        Route::post('six6s/spreedsheet',[Six6SController::class, 'Spreedsheet'])->name('six6s.spreedsheet');

        //jeetbuzz
        Route::get('/jeetbuzz', [JeetbuzzController::class, 'index'])->name('jeetbuzz');
         //send request to fetch BO for jeetbuzz
        Route::post('jeetbuzz/bo',[JeetbuzzController::class, 'jeetbuzzBO'])->name('jeetbuzz.bo');
        //send to spreedsheet
        Route::post('jeetbuzz/spreedsheet',[JeetbuzzController::class, 'Spreedsheet'])->name('jeetbuzz.spreedsheet');

        //ic88
        Route::get('/ic88', [Ic88Controller::class, 'index'])->name('ic88');
         //send request to fetch BO for jeetbuzz
        Route::post('ic88/bo',[Ic88Controller::class, 'ic88BO'])->name('ic88.bo');
        //send to spreedsheet
        Route::post('ic88/spreedsheet',[Ic88Controller::class, 'Spreedsheet'])->name('ic88.spreedsheet');

        //winrs
        Route::get('/winrs', [WinrsController::class, 'index'])->name('winrs');
         //send request to fetch BO for jeetbuzz
        Route::post('winrs/bo',[WinrsController::class, 'winrsBO'])->name('winrs.bo');

        //ctn
        Route::get('/ctn', [CtnController::class, 'index'])->name('ctn');
         //send request to fetch BO for jeetbuzz
        Route::post('ctn/bo',[CtnController::class, 'ctnBO'])->name('ctn.bo');
        //send to spreedsheet
        Route::post('ctn/spreedsheet',[CtnController::class, 'Spreedsheet'])->name('ctn.spreedsheet');

        // add cost, impressions and clicks
        Route::post('cli/insert',[BajiController::class, 'insert'])->name('cli.insert');

        //manage the bo accounts
        Route::post('manage/bo',[AsyncRequestController::class, 'manage'])->name('manage.account');
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
