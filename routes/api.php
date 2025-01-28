<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ScheduleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/scheduler', [ScheduleController::class, 'randomResults']);
Route::post('/scheduler/task', [ScheduleController::class, 'storeSchedule']);
Route::patch('/scheduler/task/{id}/status', [ScheduleController::class, 'updateStatus']);

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'I\'m lost in space.',
    ], 404);
});