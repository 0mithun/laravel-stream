<?php

use App\Http\Controllers\WebrtcStreamController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::group(['middleware'=>'auth'], function(){
    Route::get('/streaming', [WebrtcStreamController::class, 'index'])->name('stream');
    Route::get('/streaming/{streamId}', [WebrtcStreamController::class, 'consumer']);

    Route::post('/streaming-offer', [WebrtcStreamController::class, 'makeStreamOffer']);
    Route::post('/streaming-answer', [WebrtcStreamController::class, 'makeStreamAnswer']);
});
