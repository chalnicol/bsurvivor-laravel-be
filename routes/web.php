<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/send-test-email', function () {
    try {
        Mail::raw('This is a test email from Laravel via Mailtrap.', function ($message) {
            $message->to('test@example.com', 'Test User')->subject('Mailtrap Test');
        });
        return 'Email sent to Mailtrap!';
    } catch (\Exception $e) {
        return 'Email sending failed: ' . $e->getMessage();
    }
});