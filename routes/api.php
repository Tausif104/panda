<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ExamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\SpecialtyController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/registration',        [AuthController::class, 'registration']);
Route::post('/login',               [AuthController::class, 'login']);
Route::post('/forgot-password',     [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',      [AuthController::class, 'resetPassword']);
Route::post('/logout',              [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/cities',                 [CityController::class, 'getCities']);
Route::get('/city/{id}',              [CityController::class, 'getCity']);

Route::get('/specialties',                 [SpecialtyController::class, 'getSpecialties']);
Route::get('/specialty/{id}',              [SpecialtyController::class, 'getSpecialty']);

Route::get('/exams',                 [ExamController::class, 'getExams']);
Route::get('/exam/{id}',              [ExamController::class, 'getExam']);

Route::get('/packages',                 [PackageController::class, 'getPackages']);
Route::get('/package/{id}',              [PackageController::class, 'getPackage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile',                  [AuthController::class, 'get_profile']);
    Route::post('/profile',                 [AuthController::class, 'update_profile']);

    // city
    Route::post('/city',                  [CityController::class, 'create']);
    Route::post('/city/{id}',             [CityController::class, 'update']);
    Route::delete('/city/{id}',           [CityController::class, 'delete']);

    // speciality
    Route::post('/specialty',                  [SpecialtyController::class, 'create']);
    Route::post('/specialty/{id}',             [SpecialtyController::class, 'update']);
    Route::delete('/specialty/{id}',           [SpecialtyController::class, 'delete']);
    

    // Exam
    Route::post('/exam',                  [ExamController::class, 'create']);
    Route::post('/exam/{id}',             [ExamController::class, 'update']);
    Route::delete('/exam/{id}',           [ExamController::class, 'delete']);
    

    // Package
    Route::post('/package',                  [PackageController::class, 'create']);
    Route::post('/package/{id}',             [PackageController::class, 'update']);
    Route::delete('/package/{id}',           [PackageController::class, 'delete']);
    
    // order 
    Route::post('/create-order',    [OrderController::class, 'createOrder']);
    Route::post('/handle-payment',  [OrderController::class, 'handlePayment']);
    Route::post('/user-exams',      [OrderController::class, 'getUserExams']);
});