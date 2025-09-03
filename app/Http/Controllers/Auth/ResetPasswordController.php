<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password; // Keep this
use Illuminate\Validation\ValidationException; // Keep this

use App\Models\User; // Ensure you have this line to reference the User model
use App\Notifications\PasswordResetSuccess;

class ResetPasswordController extends Controller
{
   
    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        // Validate the request data
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Directly use the Password facade to reset the password
        $response = Password::reset(
            $this->credentials($request),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password), // Use bcrypt for hashing
                ])->save();
                // Optionally dispatch an event if needed
                // event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );

        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'min:8', \Illuminate\Validation\Rules\Password::defaults()], // Laravel 12+ recommends Password::defaults()
        ];
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array
     */
    protected function validationErrorMessages(): array
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request): array
    {
        return $request->only('email', 'password', 'password_confirmation', 'token');
    }


    /**
     * Get the response for a successful password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(Request $request, string $response): JsonResponse
    {

        $user = User::where('email', $request->email)->first();
        
        if ($user) {
            $user->notify(new PasswordResetSuccess($user->id, '/profile'));
        }

        return response()->json(['message' => __($response)], 200);
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, string $response): JsonResponse
    {
        throw ValidationException::withMessages([
            'email' => [__($response)],
        ]);
    }
}