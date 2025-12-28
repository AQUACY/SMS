<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dummy routes for PDF generation (prevents RouteNotFoundException)
// These routes are not actually used but prevent errors when DomPDF tries to resolve routes
// They return simple HTML responses that won't interfere with PDF generation
Route::get('/login', function () {
    return '<!DOCTYPE html><html><head><title>Login</title></head><body></body></html>';
})->name('login');

Route::get('/register', function () {
    return '<!DOCTYPE html><html><head><title>Register</title></head><body></body></html>';
})->name('register');
