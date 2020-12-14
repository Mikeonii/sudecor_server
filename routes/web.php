<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\AttendanceController;
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
Route::get('/excel',[FilesController::class,'excelFile']);
// Route::post('/excel',[FilesController::class,'extractFile']);
// export
// Route::get('/export',[FilesController::class,'export']);
// import to db
Route::post('/import',[AttendanceController::class,'import_attendance']);
// get attendance list
Route::get('/attendance',[AttendanceController::class,'index']);

// insert client
Route::get('/clients',[AttendanceController::class,'create_clients']);

// print summary
Route::get('/print_summary/{month}/{year}',[AttendanceController::class,'print_to_pdf']);