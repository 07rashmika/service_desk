<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Shows the same message whether or not a reset link was actually sent, so the
 * "Forgot password" form can't be used to find out which emails have accounts.
 * This covers unknown emails and repeated requests within the throttle window.
 */
class PasswordResetLinkRequestedResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public const MESSAGE = "If an account exists for that email, we've sent a link to reset your password.";

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => __(self::MESSAGE)], 200)
            : back()->with('status', __(self::MESSAGE));
    }
}
