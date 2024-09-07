<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ThreadsController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\ProfilesController;
use App\Http\Middleware\JwtMiddleware;
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

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
    
], function ($router) {
    Route::get('/user-profile', [AuthController::class, 'userProfile']);    
    
    Route::middleware('api_key')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);    
        Route::post('/set_profile', [UserController::class, 'updateUser']); 
       
     });

});

Route::middleware(['api_key' , 'api'])->group(function () {
    Route::post('/user/threads', [ThreadsController::class , 'userThreads']);
    Route::post('/threads/store', [ThreadsController::class , 'store']);
    Route::post('/thread', [ThreadsController::class , 'destroy']);
    Route::post('update/thread', [ThreadsController::class , 'update']);
    Route::post('thread/like', [ThreadsController::class , 'like']);
    Route::post('thread/comment', [ThreadsController::class , 'comment']);
    Route::post('threads', [ThreadController::class , 'allThreads']);
    Route::post('user/follow', [ThreadController::class , 'follow']);
    Route::post('/user/checkFollow', [ThreadController::class, 'isFollowing']);
    Route::post('/user/get', [ProfilesController::class, 'getUserBySlug']);
    Route::post('/user/threads/get', [ProfilesController::class, 'getUserThreads']);
    Route::post('/singleThread', [ProfilesController::class, 'singleThread']);
    Route::post('/deleteComment', [ThreadController::class, 'deleteComment']);

});
