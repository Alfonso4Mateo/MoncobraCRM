<?php

use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Perfil de usuario.
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

// Gestor documental.
Route::get('documentos', [DocumentosController::class, 'index'])->name('documentos.index');
Route::get('documentos/cargar', [DocumentosController::class, 'create'])->name('documentos.create');
Route::post('documentos', [DocumentosController::class, 'store'])->name('documentos.store');
Route::get('documentos/{documento}/descargar', [DocumentosController::class, 'download'])->name('documentos.download');
Route::get('documentos/{documento}/preview', [DocumentosController::class, 'preview'])->name('documentos.preview');
Route::delete('documentos/{documento}', [DocumentosController::class, 'destroy'])->name('documentos.destroy');