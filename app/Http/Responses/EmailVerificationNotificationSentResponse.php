<?php

namespace App\Http\Responses;

use App\Services\EmailVerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * After "Send a new code": says the code is on its way only when the e-mail was
 * actually handed to the mailer, and asks the customer to try again otherwise.
 */
class EmailVerificationNotificationSentResponse implements EmailVerificationNotificationSentResponseContract
{
    /** Session status shown on the code screen when the code e-mail could not be sent. */
    public const SEND_FAILED = 'verification-code-failed';

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $failed = app(EmailVerificationCodeService::class)->lastSendFailed();

        if ($request->wantsJson()) {
            return $failed
                ? new JsonResponse(['message' => 'We couldn’t send the code just now. Please try again in a few minutes.'], 503)
                : new JsonResponse('', 202);
        }

        return back()->with('status', $failed ? self::SEND_FAILED : Fortify::VERIFICATION_LINK_SENT);
    }
}
