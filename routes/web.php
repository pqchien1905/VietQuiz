<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\ClassController as AdminClassController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterAsStudentController;
use App\Http\Controllers\RegisterAsTeacherController;
use App\Http\Controllers\RemoveTeacherRoleController;
use App\Http\Controllers\Shared\HelpController;
use App\Http\Controllers\Shared\ChatbotController;
use App\Http\Controllers\Shared\NotificationController;
use App\Http\Controllers\Shared\ProfileController as SharedProfileController;
use App\Http\Controllers\Shared\SettingsController;
use App\Http\Controllers\Shared\TrashController;
use App\Http\Controllers\Shared\VipController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Teacher\AnalyticsController;
use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\ClassController;
use App\Http\Controllers\Teacher\CourseController;
use App\Http\Controllers\Teacher\GradingController;
use App\Http\Controllers\Teacher\QuizController;
use App\Http\Controllers\Teacher\QuestionController;
use App\Http\Controllers\Teacher\StudentController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\ClassController as StudentClassController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\GradeController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\StudentDashboardController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────
Route::get('/', fn () => view('pages.index'))->name('home');
Route::get('/vip/vnpay-return', [VipController::class, 'vnpayReturn'])->name('vip.vnpay.return');
Route::get('/vip/vnpay-ipn', [VipController::class, 'vnpayIpn'])->name('vip.vnpay.ipn');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    Route::get('/search', [AdminController::class, 'search'])->name('search');
    Route::get('/analytics', [AdminAnalyticsController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/export', [AdminAnalyticsController::class, 'exportAnalytics'])->name('analytics.export');
    Route::get('/users', [AdminUserController::class, 'users'])->name('users');
    Route::post('/users', [AdminUserController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{id}', [AdminUserController::class, 'showUser'])->name('users.show');
    Route::patch('/users/{id}', [AdminUserController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/{id}/restore', [AdminUserController::class, 'restoreUser'])->name('users.restore');
    Route::get('/classes', [AdminClassController::class, 'classes'])->name('classes');
    Route::post('/classes', [AdminClassController::class, 'storeClass'])->name('classes.store');
    Route::get('/classes/{id}', [AdminClassController::class, 'showClass'])->name('classes.show');
    Route::patch('/classes/{id}', [AdminClassController::class, 'updateClass'])->name('classes.update');
    Route::delete('/classes/{id}', [AdminClassController::class, 'deleteClass'])->name('classes.delete');
    Route::post('/classes/{id}/restore', [AdminClassController::class, 'restoreClass'])->name('classes.restore');
    Route::post('/classes/{id}/students', [AdminClassController::class, 'addClassStudent'])->name('classes.students.add');
    Route::delete('/classes/{id}/students/{studentId}', [AdminClassController::class, 'removeClassStudent'])->name('classes.students.remove');
    Route::get('/courses', [AdminCourseController::class, 'courses'])->name('courses');
    Route::post('/courses', [AdminCourseController::class, 'storeCourse'])->name('courses.store');
    Route::get('/courses/{id}', [AdminCourseController::class, 'showCourse'])->name('courses.show');
    Route::patch('/courses/{id}', [AdminCourseController::class, 'updateCourse'])->name('courses.update');
    Route::delete('/courses/{id}', [AdminCourseController::class, 'deleteCourse'])->name('courses.delete');
    Route::post('/courses/{id}/restore', [AdminCourseController::class, 'restoreCourse'])->name('courses.restore');
    Route::post('/courses/{id}/students', [AdminCourseController::class, 'addCourseStudent'])->name('courses.students.add');
    Route::post('/courses/{id}/sync-class-students', [AdminCourseController::class, 'syncCourseStudents'])->name('courses.students.sync');
    Route::delete('/courses/{id}/students/{studentId}', [AdminCourseController::class, 'removeCourseStudent'])->name('courses.students.remove');
    Route::get('/quizzes', [AdminQuizController::class, 'quizzes'])->name('quizzes');
    Route::post('/quizzes', [AdminQuizController::class, 'storeQuiz'])->name('quizzes.store');
    Route::get('/quizzes/{id}', [AdminQuizController::class, 'showQuiz'])->name('quizzes.show');
    Route::patch('/quizzes/{id}', [AdminQuizController::class, 'updateQuiz'])->name('quizzes.update');
    Route::delete('/quizzes/{id}', [AdminQuizController::class, 'deleteQuiz'])->name('quizzes.delete');
    Route::post('/quizzes/{id}/restore', [AdminQuizController::class, 'restoreQuiz'])->name('quizzes.restore');
    Route::delete('/quizzes/{quizId}/attempts/{studentId}', [AdminQuizController::class, 'resetQuizAttempt'])->name('quizzes.attempts.reset');
    Route::get('/questions', [AdminQuizController::class, 'questions'])->name('questions');
    Route::post('/questions', [AdminQuizController::class, 'storeQuestion'])->name('questions.store');
    Route::post('/questions/folders', [AdminQuizController::class, 'storeQuestionFolder'])->name('questions.folders.store');
    Route::patch('/questions/folders/{id}', [AdminQuizController::class, 'updateQuestionFolder'])->name('questions.folders.update');
    Route::delete('/questions/folders/{id}', [AdminQuizController::class, 'deleteQuestionFolder'])->name('questions.folders.delete');
    Route::patch('/questions/{id}', [AdminQuizController::class, 'updateQuestion'])->name('questions.update');
    Route::delete('/questions/{id}', [AdminQuizController::class, 'deleteQuestion'])->name('questions.delete');
    Route::post('/questions/{id}/restore', [AdminQuizController::class, 'restoreQuestion'])->name('questions.restore');
    Route::get('/assignments', [AdminQuizController::class, 'assignments'])->name('assignments');
    Route::post('/assignments', [AdminQuizController::class, 'storeAssignment'])->name('assignments.store');
    Route::get('/assignments/{id}', [AdminQuizController::class, 'showAssignment'])->name('assignments.show');
    Route::patch('/assignments/{id}', [AdminQuizController::class, 'updateAssignment'])->name('assignments.update');
    Route::delete('/assignments/{id}', [AdminQuizController::class, 'deleteAssignment'])->name('assignments.delete');
    Route::post('/assignments/{id}/restore', [AdminQuizController::class, 'restoreAssignment'])->name('assignments.restore');
    Route::get('/submissions', [AdminQuizController::class, 'submissions'])->name('submissions');
    Route::post('/submissions/{id}/grade', [AdminQuizController::class, 'gradeSubmission'])->name('submissions.grade');
    Route::delete('/submissions/{id}', [AdminQuizController::class, 'deleteSubmission'])->name('submissions.delete');
    Route::get('/grades', [AdminQuizController::class, 'grades'])->name('grades');
    Route::patch('/grades/{id}', [AdminQuizController::class, 'updateGrade'])->name('grades.update');
    Route::delete('/grades/{id}', [AdminQuizController::class, 'deleteGrade'])->name('grades.delete');
    Route::get('/notifications', [AdminTicketController::class, 'notifications'])->name('notifications');
    Route::post('/notifications', [AdminTicketController::class, 'storeNotification'])->name('notifications.store');
    Route::patch('/notifications/{id}/read-state', [AdminTicketController::class, 'updateNotificationReadState'])->name('notifications.read-state');
    Route::delete('/notifications/{id}', [AdminTicketController::class, 'deleteNotification'])->name('notifications.delete');
    Route::post('/notifications/{id}/restore', [AdminTicketController::class, 'restoreNotification'])->name('notifications.restore');
    Route::get('/tickets', [AdminTicketController::class, 'tickets'])->name('tickets');
    Route::patch('/tickets/{id}', [AdminTicketController::class, 'respondTicket'])->name('tickets.respond');
    Route::get('/vip', [AdminPaymentController::class, 'vip'])->name('vip');
    Route::patch('/vip/plans/{id}', [AdminPaymentController::class, 'updateVipPlan'])->name('vip.plans.update');
    Route::post('/vip/subscriptions', [AdminPaymentController::class, 'storeSubscription'])->name('vip.subscriptions.store');
    Route::patch('/vip/subscriptions/{id}', [AdminPaymentController::class, 'updateSubscription'])->name('vip.subscriptions.update');
    Route::patch('/vip/payments/{id}', [AdminPaymentController::class, 'updatePayment'])->name('vip.payments.update');
    Route::get('/promotions', [AdminPaymentController::class, 'promotions'])->name('promotions');
    Route::post('/promotions', [AdminPaymentController::class, 'storePromotion'])->name('promotions.store');
    Route::patch('/promotions/{id}', [AdminPaymentController::class, 'updatePromotion'])->name('promotions.update');
    Route::delete('/promotions/{id}', [AdminPaymentController::class, 'deletePromotion'])->name('promotions.delete');
    Route::post('/promotions/{id}/restore', [AdminPaymentController::class, 'restorePromotion'])->name('promotions.restore');
});

require __DIR__.'/auth.php';

// ── Authenticated ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Switch role (for dual account users)
    Route::get('/switch-role/{role}', function (\Illuminate\Http\Request $request, string $role) {
        $user = $request->user();
        if (!in_array($role, ['teacher', 'student'])) {
            abort(404);
        }
        if (!$user->canSwitchRole() && $user->role !== $role) {
            abort(403);
        }

        $user->forceFill([
            'role' => $role,
            'last_active_role' => $role,
        ])->save();

        $dashboardRoute = $role === 'teacher' ? 'teacher.dashboard' : 'student.dashboard';
        return redirect()->route($dashboardRoute);
    })->middleware('signed')->name('switch.role');

    // Register as teacher (for student accounts)
    Route::get('/register-as-teacher', [RegisterAsTeacherController::class, 'create'])
        ->name('register.as.teacher');

    // Register as student (for teacher accounts)
    Route::get('/register-as-student', [RegisterAsStudentController::class, 'create'])
        ->name('register.as.student');

    // Switch to student account
    Route::get('/switch-to-student', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if (!$user || $user->role !== 'teacher') {
            abort(403);
        }
        $intended = $request->query('intended');
        $targetUrl = route('student.dashboard');
        if (is_string($intended)) {
            $appUrl = $request->getSchemeAndHttpHost();
            if (str_starts_with($intended, $appUrl . '/student/') || str_starts_with($intended, '/student/')) {
                $targetUrl = $intended;
            }
        }

        if (!$user->canSwitchRole()) {
            return redirect()->route('register.as.student', ['intended' => $targetUrl]);
        }

        $user->forceFill([
            'role' => 'student',
            'can_switch_role' => true,
            'last_active_role' => 'student',
        ])->save();

        return redirect()->to($targetUrl)
            ->with('info', 'Đã chuyển sang màn Học sinh.');
    })->middleware('signed')->name('switch.to.student');

    // Switch to teacher account
    Route::get('/switch-to-teacher', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if (!$user || $user->role !== 'student') {
            abort(403);
        }

        $intended = $request->query('intended');
        $targetUrl = route('teacher.dashboard');
        if (is_string($intended)) {
            $appUrl = $request->getSchemeAndHttpHost();
            if (str_starts_with($intended, $appUrl . '/teacher/') || str_starts_with($intended, '/teacher/')) {
                $targetUrl = $intended;
            }
        }

        if (!$user->canSwitchRole()) {
            return redirect()->route('register.as.teacher', ['intended' => $targetUrl]);
        }

        $user->forceFill([
            'role' => 'teacher',
            'can_switch_role' => true,
            'last_active_role' => 'teacher',
        ])->save();

        return redirect()->to($targetUrl)
            ->with('success', 'Đã chuyển sang màn Giáo viên.');
    })->middleware('signed')->name('switch.to.teacher');

    // Remove teacher role (convert to student account)
    Route::get('/remove-teacher-role', [RemoveTeacherRoleController::class, 'create'])
        ->name('remove.teacher.role');

    // Invitation links must be reachable by any authenticated user. The
    // controller decides whether the current account can join as a student.
    Route::get('/student/join/{code}', [StudentClassController::class, 'joinByLink'])
        ->name('student.join.code');

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
        Route::post('/classes/{class}/archive', [ClassController::class, 'archive'])->name('classes.archive');
        Route::post('/classes/{class}/restore', [ClassController::class, 'restore'])->name('classes.restore');
        Route::delete('/classes/{class}/students/{student}', [ClassController::class, 'removeStudent'])->name('classes.remove-student');
        Route::post('/classes/{class}/join-requests/{student}/approve', [ClassController::class, 'approveJoinRequest'])->name('classes.join-requests.approve');
        Route::delete('/classes/{class}/join-requests/{student}/reject', [ClassController::class, 'rejectJoinRequest'])->name('classes.join-requests.reject');
        Route::post('/classes/{class}/import', [ClassController::class, 'importStudents'])->name('classes.import');
        Route::post('/classes/{class}/notify', [ClassController::class, 'sendNotification'])->name('classes.notify');
        Route::get('/classes/{class}/export', [ClassController::class, 'exportStudents'])->name('classes.export');
        Route::get('/classes/{class}/template', [ClassController::class, 'downloadTemplate'])->name('classes.template');

        // Students management
        Route::get('/students', [StudentController::class, 'index'])->name('students');
        Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
        Route::get('/students/invite-email', function () {
            $previous = url()->previous();
            $fallback = route('teacher.students');

            return redirect($previous && $previous !== url()->current() ? $previous : $fallback)
                ->with('info', 'Vui lòng dùng form mời học sinh để thêm email vào lớp.');
        });
        Route::post('/students/invite-email', [StudentController::class, 'inviteByEmail'])->name('students.invite-email');
        Route::post('/students/invite-link/{class}', [StudentController::class, 'inviteByLink'])->name('students.invite-link');
        Route::post('/students/remove', [StudentController::class, 'removeStudent'])->name('students.remove');

        // Quizzes CRUD
        Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes');
        Route::get('/quiz-create', [QuizController::class, 'create'])->name('quiz-create');
        Route::post('/quiz-folders', [QuizController::class, 'storeFolder'])->name('quiz-folders.store');
        Route::post('/quizzes/generate-ai-questions', [QuizController::class, 'generateAiQuestions'])->name('quizzes.generate-ai-questions');
        Route::post('/quizzes/import-questions-file', [QuizController::class, 'importQuestionsFile'])->name('quizzes.import-questions-file');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::patch('/quizzes/{quiz}/folder', [QuizController::class, 'moveToFolder'])->name('quizzes.move-folder');
        Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
        Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quiz-detail');
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/bulk-delete', [QuizController::class, 'bulkDestroy'])->name('quizzes.bulk-destroy');
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

        // Quiz publish/unpublish
        Route::post('/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
        Route::post('/quizzes/{quiz}/unpublish', [QuizController::class, 'unpublish'])->name('quizzes.unpublish');

        // Questions CRUD + AI/file import
        Route::get('/questions', [QuestionController::class, 'index'])->name('questions');
        Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::post('/questions/folders', [QuestionController::class, 'storeFolder'])->name('questions.folders.store');
        Route::put('/questions/folders/{folder}', [QuestionController::class, 'updateFolder'])->name('questions.folders.update');
        Route::delete('/questions/folders/{folder}', [QuestionController::class, 'destroyFolder'])->name('questions.folders.destroy');
        Route::post('/questions/generate-ai', [QuestionController::class, 'generateAi'])->name('questions.generate-ai');
        Route::post('/questions/import-file', [QuestionController::class, 'importFile'])->name('questions.import-file');
        Route::delete('/questions/bulk-delete', [QuestionController::class, 'bulkDestroy'])->name('questions.bulk-destroy');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('/questions/import-csv', [QuestionController::class, 'importCsv'])->name('questions.import-csv');

        // Assignments CRUD
        Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments');
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::get('/assignments/{assignment}/grading-board', [AssignmentController::class, 'gradingBoard'])->name('assignments.grading-board');
        Route::get('/assignments/{assignment}/grading-board/submissions/{submission}', [AssignmentController::class, 'gradingSubmission'])->name('assignments.grading-submission');
        Route::post('/assignments/{assignment}/grading-board/submissions/{submission}/ai-grade', [AssignmentController::class, 'generateAiGrade'])->name('assignments.grading-submission.ai-grade');
        Route::get('/assignments/{assignment}/attachment', [AssignmentController::class, 'previewAttachment'])->name('assignments.attachment.preview');
        Route::get('/assignments/{assignment}/attachment/inline', [AssignmentController::class, 'inlineAttachment'])->name('assignments.attachment.inline');
        Route::get('/assignments/{assignment}/attachment/converted', [AssignmentController::class, 'convertedAttachment'])->name('assignments.attachment.converted');
        Route::get('/assignments/{assignment}/attachment/download', [AssignmentController::class, 'downloadAttachment'])->name('assignments.attachment.download');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        // Courses CRUD
        Route::get('/courses', [CourseController::class, 'index'])->name('courses');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::post('/courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
        Route::post('/courses/{course}/unpublish', [CourseController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('/courses/{course}/duplicate', [CourseController::class, 'duplicate'])->name('courses.duplicate');
        Route::post('/courses/{course}/sync-students', [CourseController::class, 'syncStudents'])->name('courses.sync-students');
        Route::delete('/courses/{course}/students/{student}', [CourseController::class, 'removeStudent'])->name('courses.remove-student');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        // Grading
        Route::get('/grading', [GradingController::class, 'index'])->name('grading');
        Route::post('/grading', [GradingController::class, 'storeGrade'])->name('grading.store');
        Route::get('/grading/export', [GradingController::class, 'exportGrades'])->name('grading.export');
        Route::get('/grading/submissions/{submission}/attachment', [GradingController::class, 'inlineSubmissionAttachment'])->name('grading.submissions.attachment.inline');
        Route::get('/grading/submissions/{submission}/attachment/download', [GradingController::class, 'downloadSubmissionAttachment'])->name('grading.submissions.attachment.download');

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

        // Profile
        Route::get('/profile-view', [SharedProfileController::class, 'index'])->name('profile');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::get('/settings/export', [SettingsController::class, 'exportData'])->name('settings.export');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.unread');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::delete('/notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

        // Help
        Route::get('/help', [HelpController::class, 'index'])->name('help');
        Route::post('/help/ticket', [HelpController::class, 'submitTicket'])->name('help.ticket');
        Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

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
        Route::post('/trash/restore-selected', [TrashController::class, 'restoreSelected'])->name('trash.restore-selected');
        Route::delete('/trash/force-delete-selected', [TrashController::class, 'forceDeleteSelected'])->name('trash.force-delete-selected');

        // Support Tickets
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    });

    // ── Student ──────────────────────────────────────────
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // Classes
        Route::get('/classes', [StudentClassController::class, 'index'])->name('classes');
        Route::get('/classes/{class}', [StudentClassController::class, 'show'])->name('classes.show');
        Route::delete('/classes/{class}/leave', [StudentClassController::class, 'leave'])->name('classes.leave');

        // Courses
        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses');
        Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
        Route::get('/course-detail/{course}', [StudentCourseController::class, 'show'])->name('course-detail');

        // Quizzes
        Route::get('/quizzes', [StudentQuizController::class, 'index'])->name('quizzes');
        Route::get('/quiz-take/{quiz}', [StudentQuizController::class, 'take'])->name('quiz-take');
        Route::post('/quiz-take/{quiz}/submit', [StudentQuizController::class, 'submit'])
            ->middleware('throttle:5,1')
            ->name('quiz-take.submit');
        Route::post('/quiz-take/{quiz}/violations', [StudentQuizController::class, 'logViolation'])
            ->middleware('throttle:30,1')
            ->name('quiz-take.violations');
        Route::get('/quiz-result/{quiz}', [StudentQuizController::class, 'result'])->name('quiz-result');

        // Assignments
        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments');
        Route::get('/assignment-detail/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignment-detail');
        Route::get('/assignment-detail/{assignment}/attachment', [StudentAssignmentController::class, 'previewAttachment'])->name('assignment.attachment.preview');
        Route::get('/assignment-detail/{assignment}/attachment/inline', [StudentAssignmentController::class, 'inlineAttachment'])->name('assignment.attachment.inline');
        Route::get('/assignment-detail/{assignment}/attachment/converted', [StudentAssignmentController::class, 'convertedAttachment'])->name('assignment.attachment.converted');
        Route::get('/assignment-detail/{assignment}/attachment/download', [StudentAssignmentController::class, 'downloadAttachment'])->name('assignment.attachment.download');
        Route::get('/submissions/{submission}/attachment', [StudentAssignmentController::class, 'previewSubmissionAttachment'])->name('submissions.attachment.preview');
        Route::get('/submissions/{submission}/attachment/inline', [StudentAssignmentController::class, 'inlineSubmissionAttachment'])->name('submissions.attachment.inline');
        Route::get('/submissions/{submission}/attachment/converted', [StudentAssignmentController::class, 'convertedSubmissionAttachment'])->name('submissions.attachment.converted');
        Route::get('/submissions/{submission}/attachment/download', [StudentAssignmentController::class, 'downloadSubmissionAttachment'])->name('submissions.attachment.download');
        Route::post('/assignment-detail/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('assignment.submit');

        // Grades
        Route::get('/grades', [GradeController::class, 'index'])->name('grades');

        // Join class
        Route::get('/join-class', [StudentClassController::class, 'showJoinForm'])->name('join-class');
        Route::post('/join-class', [StudentClassController::class, 'joinByCode'])->name('join-class.submit');
        Route::delete('/join-class/{class}/cancel', [StudentClassController::class, 'cancelJoinRequest'])->name('join-class.cancel');

        // Profile
        Route::get('/profile-view', [SharedProfileController::class, 'index'])->name('profile');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::get('/settings/export', [SettingsController::class, 'exportData'])->name('settings.export');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.unread');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::delete('/notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

        // Help
        Route::get('/help', [HelpController::class, 'index'])->name('help');
        Route::post('/help/ticket', [HelpController::class, 'submitTicket'])->name('help.ticket');
        Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

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
        Route::post('/trash/restore-selected', [TrashController::class, 'restoreSelected'])->name('trash.restore-selected');
        Route::delete('/trash/force-delete-selected', [TrashController::class, 'forceDeleteSelected'])->name('trash.force-delete-selected');

        // Support Tickets
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    });
});
