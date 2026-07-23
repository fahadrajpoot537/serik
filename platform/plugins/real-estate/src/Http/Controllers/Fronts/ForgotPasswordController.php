<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\Fronts\Auth\ForgotPasswordForm;
use Botble\RealEstate\Http\Controllers\BaseController;
use Botble\RealEstate\Http\Requests\Fronts\Auth\ForgotPasswordRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Supports\AccountRegistrationExpiry;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;

class ForgotPasswordController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function showLinkRequestForm()
    {
        abort_unless(RealEstateHelper::isLoginEnabled(), 404);

        SeoHelper::setTitle(trans('plugins/real-estate::account.forgot_password'));

        Theme::addBodyAttributes(['id' => 'page-auth-forgot-password']);

        return Theme::scope(
            'real-estate.account.auth.passwords.email',
            ['form' => ForgotPasswordForm::create()],
            'plugins/real-estate::themes.auth.passwords.email'
        )->render();
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request)
    {
        abort_unless(RealEstateHelper::isLoginEnabled(), 404);

        $account = Account::query()
            ->where('email', $request->input('email'))
            ->first(['id', 'email', 'first_name', 'last_name', 'password', 'confirmed_at', 'created_at', 'password_expire']);

        if ($account && AccountRegistrationExpiry::deleteIfExpired($account)) {
            $account = null;
        }

        if ($account) {
            $pin = (string) random_int(100000, 999999);

            $account->password = $pin;
            $account->save();

            try {
                // Auth PIN must send immediately — do not rely on queue worker.
                \App\Jobs\SendAccountPinEmailJob::dispatchSync(
                    $account->email,
                    (string) $account->name,
                    $pin,
                    'forgot-password'
                );
            } catch (\Throwable $e) {
                \Log::error('[forgot-password] PIN email failed', [
                    'email' => $account->email,
                    'error' => $e->getMessage(),
                ]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'We could not send the PIN email right now. Please try again in a few minutes.',
                    ], 503);
                }

                return back()->withErrors([
                    'email' => 'We could not send the PIN email right now. Please try again in a few minutes.',
                ]);
            }
        }

        $message = 'If this email is registered, we have sent a new 6-digit PIN. Use it as your password to sign in.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => false,
                'message' => $message,
            ]);
        }

        return back()->with('auth_success_message', $message);
    }
}
