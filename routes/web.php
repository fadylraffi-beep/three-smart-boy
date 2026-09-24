<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('home');
// });

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/create', [BlogController::class, 'index'])->name("show.create");
Route::post('/create', [BlogController::class, 'create'])->name("submit.form");
Route::get('/edit/{id}', [BlogController::class, 'show_edit'])->name("show.edit");
Route::put('/edit/{id}', [BlogController::class, 'edit_form'])->name("edit.form");
Route::delete('/delete/{id}', [BlogController::class, 'delete_form'])->name("delete.form");