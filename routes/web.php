<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Projects
    Route::prefix('projects')->controller(ProjectController::class)->group(function () {
        Route::get('/', 'index')->name('projects');
    });
    // Submissions
    Route::prefix('submissions')->group(function () {
        Route::get('/', [SubmissionController::class, 'index'])->name('submissions');
        Route::get('/project/{project_id}', [SubmissionController::class, 'showAllSubmissionsBasedOnProject'])->name('submissions.showAll');
        Route::get('/submission/{submission_id}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::get('/submission/history/{history_id}', [SubmissionController::class, 'history'])->name('submissions.history');
        Route::post('/process/submission/{submission_id}', [SubmissionController::class, 'process'])->name('submissions.process');
        Route::post('/refresh/submission/{submission_id}', [SubmissionController::class, 'refresh'])->name('submissions.refresh');
        Route::get('/status/submission/{submission_id}', [SubmissionController::class, 'status'])->name('submissions.status');
        Route::post('/upload/{project_id}', [SubmissionController::class, 'upload'])->name('submissions.upload');
        Route::post('/submit', [SubmissionController::class, 'submit'])->name('submissions.submit');
    });
});

require __DIR__ . '/auth.php';
