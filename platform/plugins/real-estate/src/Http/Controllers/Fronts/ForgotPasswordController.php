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
    public const PIN_MAIL_REV = 'sync-curl-v5';

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
        $started = microtime(true);
        $mailStatus = 'no_account';

        $account = Account::query()
            ->whereRaw('LOWER(email) = ?', [$lookupEmail])
            ->first(['id', 'email', 'first_name', 'last_name', 'password', 'confirmed_at', 'created_at', 'password_expire']);

        if ($account && AccountRegistrationExpiry::isExpired($account)) {
            Log::info('[forgot-password] account expired — ask to re-register', [
                'email' => $lookupEmail,
                'account_id' => $account->id,
            ]);

            // Do not silently delete + fake "PIN sent". Tell the user clearly.
            $expiredMessage = AccountRegistrationExpiry::expiredMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => $expiredMessage,
                    'mail_status' => 'expired',
                    'pin_mail_rev' => self::PIN_MAIL_REV,
                ], 422);
            }

            return back()->withErrors(['email' => $expiredMessage]);
        }

        if ($account) {
            $pin = (string) random_int(100000, 999999);
            $email = (string) $account->email;
            $name = (string) $account->name;

            try {
                $sent = \App\Support\AccountPinMailer::send($email, $name, $pin, 'forgot-password');
            } catch (Throwable $e) {
                Log::error('[forgot-password] PIN email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $sent = false;
            }

            $elapsedMs = (int) round((microtime(true) - $started) * 1000);

            Log::info('[forgot-password] mail attempt finished', [
                'email' => $email,
                'sent' => (bool) $sent,
                'elapsed_ms' => $elapsedMs,
                'base_path' => base_path(),
                'pin_mail_rev' => self::PIN_MAIL_REV,
            ]);

            if (! $sent) {
                $failMessage = 'We could not send the PIN email right now. Please try again in a minute.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => true,
                        'message' => $failMessage,
                        'mail_status' => 'failed',
                        'pin_mail_rev' => self::PIN_MAIL_REV,
                        'elapsed_ms' => $elapsedMs,
                    ], 502);
                }

                return back()->withErrors(['email' => $failMessage]);
            }

            $account->password = $pin;
            $account->save();
            $mailStatus = 'sent';

            Log::info('[forgot-password] PIN emailed and password updated', [
                'email' => $email,
                'account_id' => $account->id,
                'pin_mail_rev' => self::PIN_MAIL_REV,
            ]);
        } else {
            Log::info('[forgot-password] no matching account (generic success returned)', [
                'email' => $lookupEmail,
                'base_path' => base_path(),
                'pin_mail_rev' => self::PIN_MAIL_REV,
            ]);
        }

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        $message = $mailStatus === 'sent'
            ? 'We have sent a new 6-digit PIN to your email. Use it as your password to sign in.'
            : 'If this email is registered, we have sent a new 6-digit PIN. Use it as your password to sign in.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => false,
                'message' => $message,
                'mail_status' => $mailStatus,
                'pin_mail_rev' => self::PIN_MAIL_REV,
                'elapsed_ms' => $elapsedMs,
            ]);
        }

        return back()->with('auth_success_message', $message);
    }
}
