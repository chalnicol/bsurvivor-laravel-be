{{-- resources/views/emails/password-reset.blade.php --}}

<x-mail::message>
# Hello, {{ $userName }}!

You are receiving this email because we received a password reset request for your account.

Click the button below to reset your password:

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This password reset link will expire in {{ $expireMinutes }} minutes.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>