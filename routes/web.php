<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Coordinator\CourseController as CoordinatorCourseController;
use App\Http\Controllers\Coordinator\EnrollmentController as CoordinatorEnrollmentController;
use App\Http\Controllers\Coordinator\SubjectController as CoordinatorSubjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\ActivityController as StudentActivityController;
use App\Http\Controllers\Student\AttemptController as StudentAttemptController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
use App\Http\Controllers\Teacher\ActivityController as TeacherActivityController;
use App\Http\Controllers\Teacher\AttemptReviewController as TeacherAttemptReviewController;
use App\Http\Controllers\Teacher\ContentController as TeacherContentController;
use App\Http\Controllers\Teacher\EvaluationController as TeacherEvaluationController;
use App\Http\Controllers\Teacher\GradeController as TeacherGradeController;
use App\Http\Controllers\Teacher\QuestionController as TeacherQuestionController;
use App\Http\Controllers\Teacher\SubjectController as TeacherSubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Raíz
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('dashboard'));

/*
|--------------------------------------------------------------------------
| Invitado (no autenticado)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Autenticado
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    | Administración — usuarios, roles y permisos
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->middleware('permission:users.view');
        Route::resource('roles', RoleController::class)->middleware('permission:roles.view');
        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('permissions.index');
    });

    /*
    | Coordinación / Administración — estructura académica
    */
    Route::middleware('role:admin,coordinator')->prefix('coordinator')->name('coordinator.')->group(function () {
        Route::resource('courses', CoordinatorCourseController::class);
        Route::resource('courses.subjects', CoordinatorSubjectController::class)->shallow();
        Route::resource('courses.enrollments', CoordinatorEnrollmentController::class)
            ->shallow()
            ->only(['index', 'store', 'update', 'destroy']);
    });

    /*
    | Docencia — asignaturas propias y sus contenidos
    */
    Route::middleware('role:admin,teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::resource('subjects', TeacherSubjectController::class)->only(['index', 'show']);
        Route::resource('subjects.contents', TeacherContentController::class)
            ->shallow()
            ->except(['show']);

        // Actividades y evaluaciones
        Route::resource('subjects.activities', TeacherActivityController::class)->shallow();

        Route::get('activities/{activity}/evaluation', [TeacherEvaluationController::class, 'edit'])
            ->name('activities.evaluation.edit');
        Route::put('activities/{activity}/evaluation', [TeacherEvaluationController::class, 'update'])
            ->name('activities.evaluation.update');

        Route::get('activities/{activity}/questions/create', [TeacherQuestionController::class, 'create'])
            ->name('activities.questions.create');
        Route::post('activities/{activity}/questions', [TeacherQuestionController::class, 'store'])
            ->name('activities.questions.store');
        Route::get('questions/{question}/edit', [TeacherQuestionController::class, 'edit'])->name('questions.edit');
        Route::put('questions/{question}', [TeacherQuestionController::class, 'update'])->name('questions.update');
        Route::delete('questions/{question}', [TeacherQuestionController::class, 'destroy'])->name('questions.destroy');

        // Calificaciones
        Route::get('activities/{activity}/gradebook', [TeacherGradeController::class, 'index'])
            ->name('activities.gradebook');
        Route::post('activities/{activity}/grades', [TeacherGradeController::class, 'store'])
            ->name('activities.grades.store');

        Route::get('attempts/{attempt}/review', [TeacherAttemptReviewController::class, 'edit'])
            ->name('attempts.review');
        Route::put('attempts/{attempt}/review', [TeacherAttemptReviewController::class, 'update'])
            ->name('attempts.review.update');
    });

    /*
    | Aprendizaje — cursos y asignaturas en los que el estudiante está inscrito
    */
    Route::middleware('role:admin,student')->prefix('student')->name('student.')->group(function () {
        Route::resource('courses', StudentCourseController::class)->only(['index', 'show']);
        Route::get('subjects/{subject}', StudentSubjectController::class)->name('subjects.show');

        // Actividades, intentos y resultados
        Route::get('activities/{activity}', [StudentActivityController::class, 'show'])->name('activities.show');
        Route::post('activities/{activity}/attempts', [StudentAttemptController::class, 'store'])
            ->name('activities.attempts.store');
        Route::get('attempts/{attempt}', [StudentAttemptController::class, 'show'])->name('attempts.show');
        Route::post('attempts/{attempt}/save', [StudentAttemptController::class, 'update'])->name('attempts.save');
        Route::post('attempts/{attempt}/submit', [StudentAttemptController::class, 'submit'])->name('attempts.submit');
    });
});
