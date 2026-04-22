<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController as ApiClassController;
use App\Http\Controllers\Api\CourseController as ApiCourseController;
use App\Http\Controllers\Api\GradeController as ApiGradeController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\QuizController as ApiQuizController;
use App\Http\Controllers\Api\AssignmentController as ApiAssignmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public auth
    Route::post('/login', [AuthController::class, 'login']);

    // Protected API
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Classes
        Route::get('/classes', [ApiClassController::class, 'index']);
        Route::post('/classes/join', [ApiClassController::class, 'joinByCode']);

        // Courses
        Route::get('/courses', [ApiCourseController::class, 'index']);
        Route::get('/courses/{course}', [ApiCourseController::class, 'show']);

        // Quizzes
        Route::get('/quizzes', [ApiQuizController::class, 'index']);
        Route::get('/quizzes/{quiz}', [ApiQuizController::class, 'show']);
        Route::post('/quizzes/{quiz}/start', [ApiQuizController::class, 'start']);
        Route::post('/quizzes/{quiz}/submit', [ApiQuizController::class, 'submit']);

        // Assignments
        Route::get('/assignments', [ApiAssignmentController::class, 'index']);
        Route::get('/assignments/{assignment}', [ApiAssignmentController::class, 'show']);
        Route::post('/assignments/{assignment}/submit', [ApiAssignmentController::class, 'submit']);

        // Grades
        Route::get('/grades', [ApiGradeController::class, 'index']);

        // Notifications
        Route::get('/notifications', [ApiNotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [ApiNotificationController::class, 'markAsRead']);
    });
});
