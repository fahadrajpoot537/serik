<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\ACL\Traits\RegistersUsers;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\Fronts\Auth\RegisterForm;
use Botble\RealEstate\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Notifications\ConfirmEmailNotification;
use Botble\RealEstate\Supports\AccountRegistrationExpiry;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RegisterController extends BaseController
{
    use RegistersUsers;

    protected string $redirectTo = '/';

    public function __construct()
    {
        $this->redirectTo = route('public.index');
    }

    public function showRegistrationForm()
    {
        abort_unless(RealEstateHelper::isRegisterEnabled(), 404);

        // store previous page
        session(['url.intended' => url()->previous()]);

        SeoHelper::setTitle(trans('plugins/real-estate::account.register'));

        Theme::addBodyAttributes(['id' => 'page-auth-register']);

        return Theme::scope(
            'real-estate.account.auth.register',
            ['form' => RegisterForm::create()],
            'plugins/real-estate::themes.auth.register'
        )->render();
    }

    public function confirm(int|string $id, Request $request)
    {
        abort_unless(RealEstateHelper::isRegisterEnabled(), 404);

        abort_unless(URL::hasValidSignature($request), 404);

        $account = Account::query()->findOrFail($id);

        $account->confirmed_at = Carbon::now();
        $account->save();

        $this->guard()->login($account);

        return $this
            ->httpResponse()
            ->setNextUrl(route('public.index')) // redirect to homepage
            ->setMessage(trans('plugins/real-estate::account.email_confirmed_success'));
    }

    protected function guard()
    {
        return auth('account');
    }

    public function resendConfirmation(Request $request)
    {
        abort_unless(RealEstateHelper::isRegisterEnabled(), 404);

        /**
         * @var Account $account
         */
        $account = Account::query()->where('email', $request->input('email'))->first();

        if ($account && AccountRegistrationExpiry::deleteIfExpired($account)) {
            $account = null;
        }

        if (!$account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/real-estate::account.account_not_found'));
        }

        $this->sendConfirmationToUser($account);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/real-estate::account.confirmation_resent'));
    }

    protected function sendConfirmationToUser(Account $account): void
    {
        $account->notify(app(ConfirmEmailNotification::class));
    }

    public function register(RegisterRequest $request)
    {
        abort_unless(RealEstateHelper::isRegisterEnabled(), 404);

        $existing = Account::query()->where('email', $request->input('email'))->first();
        if ($existing && AccountRegistrationExpiry::deleteIfExpired($existing)) {
            $existing = null;
        }

        if (!$request->has('username')) {
            $request->merge([
                'username' => Account::generateUsername(
                    $request->input('first_name'),
                    $request->input('last_name')
                )
            ]);
        }

        // Generate numeric PIN if password not provided
        $unhashedPin = $request->input('password') ?: (string) rand(100000, 999999);
        $request->merge(['password' => $unhashedPin]);

        /**
         * @var Account $account
         */
        $account = $this->create($request->input());

        event(new Registered($account));

        try {
            $sent = EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'account_name' => $account->name,
                    'account_email' => $account->email,
                    'account_password' => $unhashedPin,
                ])
                ->sendUsingTemplate('account-registered', $account->email);

            if (! $sent) {
                \Log::warning('[register] PIN email template disabled or not sent', [
                    'email' => $account->email,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('[register] PIN email failed: ' . $e->getMessage(), [
                'email' => $account->email,
            ]);
        }

        // Verification email disabled – only PIN email is sent
        // if (setting('verify_account_email', false)) {
        //     $this->sendConfirmationToUser($account);
        //
        //     $this->registered($request, $account);
        //
        //     $message = trans('plugins/real-estate::account.verification_email_sent');
        //
        //     return $this
        //         ->httpResponse()
        //         ->setNextUrl('/register-thanks')
        //         ->with(['auth_warning_message' => $message])
        //         ->setMessage($message);
        // }

        $account->confirmed_at = Carbon::now();

        $account->is_public_profile = false;

        $account->save();

        // Do not auto-login. The user must login with their generated PIN.
        // $this->guard()->login($account);

        return $this
            ->httpResponse()
            ->setNextUrl(route('public.account.login'))
            ->setMessage('Registration successful! Please check your email for the 6-digit PIN to login.');
    }

    protected function create(array $data)
    {
        return Account::query()->forceCreate([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'username' => $data['username'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'password_expire' => Carbon::now(),
        ]);
    }

    public function getVerify()
    {
        abort_unless(RealEstateHelper::isRegisterEnabled(), 404);

        return view('plugins/real-estate::account.auth.verify');
    }

    public function checkEmail(Request $request)
    {
        abort_unless(RealEstateHelper::isRegisterEnabled(), 404);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $account = Account::query()->where('email', $request->input('email'))->first();

        if ($account && AccountRegistrationExpiry::deleteIfExpired($account)) {
            $account = null;
        }

        $exists = $account !== null;

        return response()->json([
            'exists' => $exists,
            'message' => $exists
                ? 'This email is already registered. Please sign in instead.'
                : null,
        ]);
    }
}
