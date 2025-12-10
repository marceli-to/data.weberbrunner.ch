<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\JuryController;
use App\Http\Controllers\Api\LectureController;
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

// Team Members
Route::get('/team-members/filter-options', [TeamMemberController::class, 'filterOptions']);
Route::get('/team-members', [TeamMemberController::class, 'index']);
Route::get('/team-members/{teamMember}', [TeamMemberController::class, 'show']);
Route::post('/team-members', [TeamMemberController::class, 'store']);
Route::put('/team-members/reorder', [TeamMemberController::class, 'reorder']);
Route::put('/team-members/{teamMember}', [TeamMemberController::class, 'update']);
Route::delete('/team-members/{teamMember}', [TeamMemberController::class, 'destroy']);

// Awards
Route::get('/awards/filter-options', [AwardController::class, 'filterOptions']);
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{award}', [AwardController::class, 'show']);
Route::post('/awards', [AwardController::class, 'store']);
Route::put('/awards/reorder', [AwardController::class, 'reorder']);
Route::put('/awards/{award}', [AwardController::class, 'update']);
Route::delete('/awards/{award}', [AwardController::class, 'destroy']);

// Jury
Route::get('/jury/filter-options', [JuryController::class, 'filterOptions']);
Route::get('/jury', [JuryController::class, 'index']);
Route::get('/jury/{jury}', [JuryController::class, 'show']);
Route::post('/jury', [JuryController::class, 'store']);
Route::put('/jury/reorder', [JuryController::class, 'reorder']);
Route::put('/jury/{jury}', [JuryController::class, 'update']);
Route::delete('/jury/{jury}', [JuryController::class, 'destroy']);

// Lectures
Route::get('/lectures/filter-options', [LectureController::class, 'filterOptions']);
Route::get('/lectures', [LectureController::class, 'index']);
Route::get('/lectures/{lecture}', [LectureController::class, 'show']);
Route::post('/lectures', [LectureController::class, 'store']);
Route::put('/lectures/reorder', [LectureController::class, 'reorder']);
Route::put('/lectures/{lecture}', [LectureController::class, 'update']);
Route::delete('/lectures/{lecture}', [LectureController::class, 'destroy']);
