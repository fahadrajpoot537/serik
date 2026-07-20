<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\ACL\Traits\AuthenticatesUsers;
use Botble\ACL\Traits\LogoutGuardTrait;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\Fronts\Auth\LoginForm;
use Botble\RealEstate\Http\Controllers\BaseController;
use Botble\RealEstate\Http\Requests\Fronts\Auth\LoginRequest;
use Botble\RealEstate\Supports\AccountRegistrationExpiry;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;

class LoginController extends BaseController
{
    use AuthenticatesUsers, LogoutGuardTrait {
        AuthenticatesUsers::attemptLogin as baseAttemptLogin;
    }

    public string $redirectTo = '/';

    public function __construct()
    {
        parent::__construct();

        $this->redirectTo = route('public.account.dashboard');
    }

    public function showLoginForm()
    {
        abort_unless(RealEstateHelper::isLoginEnabled(), 404);
    
        session(['url.intended' => url()->previous()]);
    
        SeoHelper::setTitle(trans('plugins/real-estate::account.login'));
    
        Theme::addBodyAttributes(['id' => 'page-auth-login']);
    
        return Theme::scope(
            'real-estate.account.auth.login',
            ['form' => LoginForm::create()],
            'plugins/real-estate::themes.auth.login'
        )->render();
    }

    protected function guard()
    {
        return auth('account');
    }

    public function login(LoginRequest $request)
    {
        abort_unless(RealEstateHelper::isLoginEnabled(), 404);

        $request->merge([$this->username() => $request->input('email')]);

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {

            if ($request->ajax()) {
                return response()->json([
                    'error' => false,
                    'redirect' => url()->previous(),
                ]);
            }
        
            return redirect()->intended(url()->previous());
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse();
    }
    
    protected function redirectTo()
    {
        return url()->previous();
    }

  // use Carbon\Carbon;

protected function attemptLogin(Request $request)
{
    if ($this->guard()->validate($this->credentials($request))) {
        $account = $this->guard()->getLastAttempted();

        // EMAIL VERIFICATION CHECK
        if (setting('verify_account_email', false) && empty($account->confirmed_at)) {
            throw ValidationException::withMessages([
                'confirmation' => [
                    trans('plugins/real-estate::account.email_not_confirmed', [
                        'resend_link' => route('public.account.resend_confirmation', ['email' => $account->email]),
                    ]),
                ],
            ]);
        }

        // BLOCKED USER CHECK
        if ($account->blocked_at) {
            $message = trans('plugins/real-estate::account.blocked_account_message');

            if ($account->blocked_reason) {
                $message .= ' ' . trans('plugins/real-estate::account.blocked_reason_label', [
                    'reason' => $account->blocked_reason
                ]);
            }

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        // TREB: registration expires after 90 days — delete account so user can re-register
        if (AccountRegistrationExpiry::isExpired($account)) {
            $this->guard()->logout();
            $account->delete();

            throw ValidationException::withMessages([
                'email' => [AccountRegistrationExpiry::expiredMessage()],
            ]);
        }

        return $this->baseAttemptLogin($request);
    }

    return false;
}

    public function username()
    {
        return filter_var(request()->input('email'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    public function logout(Request $request)
    {
        abort_unless(RealEstateHelper::isLoginEnabled(), 404);

        $activeGuards = 0;
        $this->guard()->logout();

        foreach (config('auth.guards', []) as $guard => $guardConfig) {
            if ($guardConfig['driver'] !== 'session') {
                continue;
            }
            if ($this->isActiveGuard($request, $guard)) {
                $activeGuards++;
            }
        }

        if (! $activeGuards) {
            $request->session()->flush();
            $request->session()->regenerate();
        }

        $this->loggedOut($request);

        return redirect(route('public.index'));
    }
}
