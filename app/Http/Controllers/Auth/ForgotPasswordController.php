<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password; // Keep this

class ForgotPasswordController extends Controller
{
    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        // Validate the email using Laravel's validator
        $request->validate(['email' => 'required|email']);

        // Directly use the Password facade to send the reset link
        $response = Password::sendResetLink(
            $request->only('email')
        );

        // Handle the response from the Password facade
        return $response == Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($request, $response)
                    : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, string $response): JsonResponse
    {
        return response()->json(['message' => __($response)], 200);
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, string $response): JsonResponse
    {
        // Using ValidationException to send back errors in a standard format
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => [__($response)],
        ]);
    }
}