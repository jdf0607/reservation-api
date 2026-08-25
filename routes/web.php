<?php

use Illuminate\Support\Facades\Route;

// Todas las rutas que no son /api van al frontend Vue (SPA)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
