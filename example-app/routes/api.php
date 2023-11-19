<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\LivroController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [TokenController::class, 'getToken']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/livros', [LivroController::class, 'store']);
    Route::get('/livros', [LivroController::class, 'index']);
    Route::post('/livros/{livro}/importar-indices-xml', [LivroController::class, 'importar']);
});
