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
use Illuminate\Support\Facades\Log;
use Throwable;

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

        $lookupEmail = strtolower(trim((string) $request->input('email')));

        $account = Account::query()
            ->whereRaw('LOWER(email) = ?', [$lookupEmail])
            ->first(['id', 'email', 'first_name', 'last_name', 'password', 'confirmed_at', 'created_at', 'password_expire']);

        if ($account && AccountRegistrationExpiry::deleteIfExpired($account)) {
            $account = null;
        }

        if ($account) {
            $pin = (string) random_int(100000, 999999);
            $email = (string) $account->email;
            $name = (string) $account->name;

            // Sync Resend only — never queue / afterResponse (caused 10–20 min delays).
            $started = microtime(true);
            try {
                $sent = \App\Support\AccountPinMailer::send($email, $name, $pin, 'forgot-password');
            } catch (Throwable $e) {
                Log::error('[forgot-password] PIN email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $sent = false;
            }
            Log::info('[forgot-password] mail attempt finished', [
                'email' => $email,
                'sent' => (bool) $sent,
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            if (! $sent) {
                $failMessage = 'We could not send the PIN email right now. Please try again in a minute.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => true,
                        'message' => $failMessage,
                    ], 502);
                }

                return back()->withErrors(['email' => $failMessage]);
            }

            $account->password = $pin;
            $account->save();

            Log::info('[forgot-password] PIN emailed and password updated', [
                'email' => $email,
                'account_id' => $account->id,
            ]);
        } else {
            Log::info('[forgot-password] no matching account (generic success returned)', [
                'email' => (string) $request->input('email'),
            ]);
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
