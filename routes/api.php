<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\HolidayController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/store',[FilesController::class,'store_excel']);
// get attendance list
Route::get('/attendance_list',[AttendanceController::class,'get_attendance']);
// get client list 
Route::get('/client_list',[ClientsController::class,'index']);
// update client
Route::put('/client',[ClientsController::class,'store']);
// get single client
Route::get('/client/{id}',[ClientsController::class,'show']);
// get individual attendance 
Route::get('/attendance_individual_list/{client_id}',[AttendanceController::class, 'get_individual_attendance']);
// delete double entry
Route::delete('/delete_double_entry/{year}/{month}',[AttendanceController::class,'delete_double_entry']);
// import attendance to db
Route::post('/import',[AttendanceController::class,'import_attendance']);
// get attendance_summary
Route::get('/get_attendance_summary/{client_id}',[AttendanceController::class,'get_attendance_summary']);
// calculate summary
Route::post('/calculate_summary',[AttendanceController::class,'calculate_summary']);
// insert holiday
Route::post('/add_holiday',[HolidayController::class,'insert_holiday']);	
// get holidays
Route::get('/holidays',[HolidayController::class,'index']);
// get full per month
Route::post('/client_full_info',[AttendanceController::class,'client_full_info']);

// insert client
Route::get('/create_clients',[AttendanceController::class,'create_clients']);

Route::get('/export_clients',[FilesController::class,'export_clients']);