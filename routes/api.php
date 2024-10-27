<?php

use App\Models\ReceivedCapsule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\CapsuleController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReceivedCapsuleController;

Route::post('/user', [Controller::class, 'register']); 

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/showName/{id}', [UserController::class, 'usernameView']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',[UserController::class, 'logout']);
});

// Route for adding images to a specific capsule
Route::post('/capsules/{capsule}/images', [ImageController::class, 'store']);
Route::delete('/images/{imageId}', [ImageController::class, 'destroy']);

Route::middleware('api')->group(function () {
    // Route::put('/capsules/{id}', [CapsuleController::class, 'update']);
});

Route::middleware('auth:sanctum')->post('/profile/upload', [ProfileController::class, 'uploadProfilePic']);
Route::middleware('auth:sanctum')->get('/profile/show', [ProfileController::class, 'showProfile']);

Route::apiResource('profile', ProfileController::class);

// Route::apiResource('images', ImageController::class);
Route::apiResource('capsules', CapsuleController::class);
Route::apiResource('receivedCapsules', ReceivedCapsuleController::class);