<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\RedirectsAfterAuthentication;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirms a customer's e-mail address with the 6-digit code they were sent,
 * then continues to where they were going (e.g. the product that needed sign-in).
 */
class EmailVerificationCodeController extends Controller
{
    use RedirectsAfterAuthentication;

    public function __construct(private readonly EmailVerificationCodeService $codes) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->codes->verify($user, $request->string('code')->toString());

        return $this->redirectFor($request)->with('status', 'email-verified');
    }
}
