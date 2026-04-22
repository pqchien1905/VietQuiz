<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Teacher\AnalyticsController;
use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\ClassController;
use App\Http\Controllers\Teacher\CourseController;
use App\Http\Controllers\Teacher\GradingController;
use App\Http\Controllers\Teacher\HelpController;
use App\Http\Controllers\Teacher\NotificationController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use App\Http\Controllers\Teacher\QuizController;
use App\Http\Controllers\Teacher\QuestionController;
use App\Http\Controllers\Teacher\SettingsController;
use App\Http\Controllers\Teacher\StudentController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TrashController;
use App\Http\Controllers\Teacher\VipController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\ClassController as StudentClassController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\GradeController;
use App\Http\Controllers\Student\HelpController as StudentHelpController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\SettingsController as StudentSettingsController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\TrashController as StudentTrashController;
use App\Http\Controllers\Student\VipController as StudentVipController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────
Route::get('/', fn () => view('pages.index'))->name('home');

require __DIR__.'/auth.php';

// ── Authenticated ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Teacher ──────────────────────────────────────────
    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

        // Classes CRUD
        Route::get('/classes', [ClassController::class, 'index'])->name('classes');
        Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
        Route::get('/classes/{class}', [ClassController::class, 'show'])->name('class-detail');
        Route::put('/classes/{class}', [ClassController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{class}', [ClassController::class, 'destroy'])->name('classes.destroy');
        Route::delete('/classes/{class}/students/{student}', [ClassController::class, 'removeStudent'])->name('classes.remove-student');

        // Students management
        Route::get('/students', [StudentController::class, 'index'])->name('students');
        Route::post('/students/invite-email', [StudentController::class, 'inviteByEmail'])->name('students.invite-email');
        Route::post('/students/invite-link/{class}', [StudentController::class, 'inviteByLink'])->name('students.invite-link');

        // Quizzes CRUD
        Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes');
        Route::get('/quiz-create', [QuizController::class, 'create'])->name('quiz-create');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quiz-detail');
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

        // Questions CRUD + CSV import
        Route::get('/questions', [QuestionController::class, 'index'])->name('questions');
        Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('/questions/import-csv', [QuestionController::class, 'importCsv'])->name('questions.import-csv');

        // Assignments CRUD
        Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments');
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        // Courses CRUD
        Route::get('/courses', [CourseController::class, 'index'])->name('courses');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        // Grading
        Route::get('/grading', [GradingController::class, 'index'])->name('grading');
        Route::post('/grading', [GradingController::class, 'storeGrade'])->name('grading.store');
        Route::get('/grading/export', [GradingController::class, 'exportGrades'])->name('grading.export');

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

        // Profile
        Route::get('/profile-view', [TeacherProfileController::class, 'index'])->name('profile');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::delete('/notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

        // Help
        Route::get('/help', [HelpController::class, 'index'])->name('help');
        Route::post('/help/ticket', [HelpController::class, 'submitTicket'])->name('help.ticket');

        // VIP
        Route::get('/vip', [VipController::class, 'index'])->name('vip');
        Route::post('/vip/subscribe', [VipController::class, 'subscribe'])->name('vip.subscribe');
        Route::post('/vip/cancel', [VipController::class, 'cancel'])->name('vip.cancel');

        // Trash
        Route::get('/trash', [TrashController::class, 'index'])->name('trash');
        Route::post('/trash/restore/{type}/{id}', [TrashController::class, 'restore'])->name('trash.restore');
        Route::delete('/trash/force-delete/{type}/{id}', [TrashController::class, 'forceDelete'])->name('trash.force-delete');
        Route::post('/trash/restore-all', [TrashController::class, 'restoreAll'])->name('trash.restore-all');
        Route::delete('/trash/force-delete-all', [TrashController::class, 'forceDeleteAll'])->name('trash.force-delete-all');
    });

    // ── Student ──────────────────────────────────────────
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // Courses
        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses');
        Route::get('/course-detail/{course}', [StudentCourseController::class, 'show'])->name('course-detail');

        // Quizzes
        Route::get('/quizzes', [StudentQuizController::class, 'index'])->name('quizzes');
        Route::get('/quiz-take/{quiz}', [StudentQuizController::class, 'take'])->name('quiz-take');
        Route::post('/quiz-take/{quiz}/submit', [StudentQuizController::class, 'submit'])->name('quiz-take.submit');
        Route::get('/quiz-result/{quiz}', [StudentQuizController::class, 'result'])->name('quiz-result');

        // Assignments
        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments');
        Route::get('/assignment-detail/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignment-detail');
        Route::post('/assignment-detail/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('assignment.submit');

        // Grades
        Route::get('/grades', [GradeController::class, 'index'])->name('grades');

        // Join class
        Route::get('/join-class', [StudentClassController::class, 'showJoinForm'])->name('join-class');
        Route::post('/join-class', [StudentClassController::class, 'joinByCode'])->name('join-class.submit');

        // Profile
        Route::get('/profile-view', [StudentProfileController::class, 'index'])->name('profile');

        // Settings
        Route::get('/settings', [StudentSettingsController::class, 'index'])->name('settings');
        Route::post('/settings/profile', [StudentSettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/settings/password', [StudentSettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/settings/notifications', [StudentSettingsController::class, 'updateNotifications'])->name('settings.notifications');

        // Notifications
        Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/{notification}/read', [StudentNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [StudentNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications', [StudentNotificationController::class, 'clearAll'])->name('notifications.clear-all');

        // Help
        Route::get('/help', [StudentHelpController::class, 'index'])->name('help');
        Route::post('/help/ticket', [StudentHelpController::class, 'submitTicket'])->name('help.ticket');

        // VIP
        Route::get('/vip', [StudentVipController::class, 'index'])->name('vip');
        Route::post('/vip/subscribe', [StudentVipController::class, 'subscribe'])->name('vip.subscribe');

        // Trash
        Route::get('/trash', [StudentTrashController::class, 'index'])->name('trash');

        // Join by code (public endpoint)
        Route::get('/join/{code}', [StudentClassController::class, 'joinByCode'])->name('join.code');
    });
});
