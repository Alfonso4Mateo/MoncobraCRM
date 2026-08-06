<?php

use App\Http\Controllers\CursoController;
use App\Http\Controllers\PersonalCursoController;
use Illuminate\Support\Facades\Route;

Route::get('cursos/gestion', [CursoController::class, 'gestion'])->name('cursos.gestion');
Route::get('cursos/alertas', [CursoController::class, 'alertasCaducidad'])->name('cursos.alertas');
Route::resource('cursos', CursoController::class)->except(['show']);
Route::put('personal/{personal}/cursos', [PersonalCursoController::class, 'update'])->name('personal.cursos.update');
Route::delete('personal/{personal}/cursos/{curso}', [PersonalCursoController::class, 'destroy'])->name('personal.cursos.destroy');
Route::get('/cursos/{curso}', [App\Http\Controllers\CursoController::class, 'show'])->name('cursos.show');
Route::get('/cursos/{curso}/exportar', [App\Http\Controllers\CursoController::class, 'export'])->name('cursos.export');