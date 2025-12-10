<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

// Projects
Route::get('/projects/filter-options', [ProjectController::class, 'filterOptions']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::put('/projects/{project}', [ProjectController::class, 'update']);
Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
Route::put('/projects/reorder', [ProjectController::class, 'reorder']);

// Project Texts
Route::get('/projects/{project}/texts', [ProjectController::class, 'texts']);
Route::post('/projects/{project}/texts', [ProjectController::class, 'storeText']);
Route::put('/projects/{project}/texts/{text}', [ProjectController::class, 'updateText']);
Route::delete('/projects/{project}/texts/{text}', [ProjectController::class, 'destroyText']);

// Project Images
Route::get('/projects/{project}/images', [ProjectController::class, 'images']);
Route::put('/projects/{project}/images/{image}', [ProjectController::class, 'updateImage']);
Route::delete('/projects/{project}/images/{image}', [ProjectController::class, 'destroyImage']);
Route::put('/projects/{project}/images/reorder', [ProjectController::class, 'reorderImages']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::put('/categories/{category}', [CategoryController::class, 'update']);
Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
