<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css" />
@php
    use Theme\homzen\Supports\RecaptchaHelper;
@endphp
<script src="https://www.google.com/recaptcha/api.js?onload=initSerikRecaptcha&render=explicit" async defer></script>
<style>
    #modalLogin.modal:not(.show) {
        display: none !important;
        opacity: 0;
        pointer-events: none;
    }

    #modalLogin.modal.show {
        display: block !important;
    }

    /* Modern Unified Auth Modal Styles */
    .auth-modal-dialog {
        max-width: 480px;
        border-radius: 20px;
    }

    .auth-modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .auth-header-bg {
        background: #9dbdfd;
        padding: 30px 30px 20px;
        position: relative;
        color: #0f172a;
        text-align: center;
    }

    .auth-header-bg .btn-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background-color: rgba(15, 23, 42, 0.12);
        border-radius: 50%;
        opacity: 1;
        filter: none;
    }

    .auth-header-bg h3 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
    }

    .auth-header-bg p {
        margin: 8px 0 0;
        color: #1e3a5f;
        font-size: 14px;
    }

    .auth-body {
        padding: 0;
        position: relative;
        overflow: hidden;
        /* For sliding effect */
        min-height: 380px;
    }

    .auth-slider-container {
        display: flex;
        width: 200%;
        /* Two panels: Login (50%) and Register (50%) */
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .auth-slider-container.show-register {
        transform: translateX(-50%);
    }

    .auth-panel {
        width: 50%;
        /* takes up half of 200% = 100% of body */
        padding: 30px;
        flex-shrink: 0;
    }

    .form-group label {
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }

    .form-control {
        border-radius: 10px;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 15px;
        transition: all 0.2s;
    }

    .form-control:focus {
        background-color: #fff;
        border-color: rgb(2, 85, 161);
        box-shadow: 0 0 0 3px rgba(2, 85, 161, 0.12);
    }

    .btn-auth-primary {
        background: rgb(2, 85, 161);
        color: #fff;
        font-weight: 700;
        font-size: 16px;
        padding: 14px;
        width: 100%;
        border-radius: 12px;
        border: none;
        margin-top: 15px;
        box-shadow: 0 4px 12px rgba(2, 85, 161, 0.25);
        transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
    }

    .btn-auth-primary:hover {
        transform: translateY(-2px);
        background: rgb(2, 72, 138);
        box-shadow: 0 6px 16px rgba(2, 85, 161, 0.35);
        color: #fff;
    }

    .auth-switch {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: #64748b;
    }

    .auth-switch a {
        color: rgb(2, 85, 161);
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .auth-switch a:hover {
        text-decoration: underline;
    }

    .auth-forgot-link {
        display: inline-block;
        margin-top: 12px;
        color: rgb(2, 85, 161);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }

    .auth-forgot-link:hover {
        text-decoration: underline;
    }

    /* Multi-step logic for registration */
    .reg-step {
        display: none;
        animation: fadeInStep 0.4s ease;
    }

    .reg-step.active {
        display: block;
    }

    @keyframes fadeInStep {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .iti {
        width: 100% !important;
    }

    .back-btn {
        background: none;
        border: none;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        padding: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .back-btn:hover {
        color: #0f172a;
    }

    .error-msg {
        color: #ef4444;
        font-size: 13px;
        margin-top: 5px;
        display: none;
    }

    .error-msg.checking {
        color: #64748b;
        display: block;
    }

    .form-control.is-invalid {
        border-color: #ef4444;
    }

    .terms-box {
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        background: #f8fafc;
        font-size: 13px;
        line-height: 1.55;
        color: #334155;
        margin-bottom: 16px;
    }

    .terms-box h5 {
        font-size: 14px;
        font-weight: 700;
        margin: 14px 0 6px;
        color: var(--main-header-text-color, #161e2d);
    }

    .terms-box h5:first-child {
        margin-top: 0;
    }

    .terms-agree {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
        color: #334155;
        margin-bottom: 8px;
    }

    .terms-agree input {
        margin-top: 4px;
        accent-color: rgb(2, 85, 161);
    }

    @media (max-width: 767px) {
        #modalLogin .auth-modal-dialog {
            max-width: calc(100% - 16px);
            margin: 8px auto;
        }

        #modalLogin .auth-modal-content {
            max-height: calc(100dvh - 16px);
            overflow-y: auto;
            border-radius: 14px;
        }

        #modalLogin .auth-header-bg {
            padding: 16px 14px 10px;
        }

        #modalLogin .auth-header-bg .btn-close {
            top: 12px;
            right: 12px;
        }

        #modalLogin .auth-header-bg h3 {
            font-size: 18px;
        }

        #modalLogin .auth-header-bg p {
            font-size: 12px;
            margin-top: 4px;
        }

        #modalLogin .auth-body {
            min-height: auto;
        }

        #modalLogin .auth-panel {
            padding: 14px 14px 16px;
        }

        #modalLogin .form-group label {
            font-size: 13px;
            margin-bottom: 6px;
        }

        #modalLogin .form-control {
            padding: 9px 12px;
            font-size: 14px;
            border-radius: 8px;
        }

        #modalLogin .btn-auth-primary {
            padding: 10px;
            font-size: 14px;
            margin-top: 10px;
            border-radius: 10px;
        }

        #modalLogin .auth-switch {
            margin-top: 12px;
            font-size: 12px;
        }

        #modalLogin .auth-forgot-link {
            font-size: 12px;
            margin-top: 8px;
        }

        #modalLogin .back-btn {
            margin-bottom: 12px;
            font-size: 13px;
        }

        #modalLogin .terms-box {
            max-height: 120px;
            padding: 10px 12px;
            font-size: 11px;
            line-height: 1.45;
            margin-bottom: 12px;
        }

        #modalLogin .terms-box h5 {
            font-size: 12px;
            margin: 10px 0 4px;
        }

        #modalLogin .terms-agree {
            font-size: 12px;
            gap: 8px;
        }

        #modalLogin #forgotSuccessView h4 {
            font-size: 18px;
        }

        #modalLogin #forgotSuccessView p {
            font-size: 13px;
        }
    }
</style>

<!-- Unified Auth Modal -->
<div class="modal fade" id="modalLogin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered auth-modal-dialog">
        <div class="modal-content auth-modal-content">

            <div class="auth-header-bg">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h3 id="authTitle">Welcome Back</h3>
                <p id="authSubtitle">Sign in to unlock exclusive property details</p>
            </div>

            <div class="auth-body">
                <div class="auth-slider-container" id="authSlider">

                    <!-- LOGIN PANEL -->
                    <div class="auth-panel">
                        <div id="loginView">
                            <form id="customLoginForm" action="{{ route('public.account.login') }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <label>Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="Enter your email"
                                        required>
                                    <div class="error-msg" id="loginEmailErr"></div>
                                </div>
                                <div class="form-group mb-4">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Enter your password" required>
                                    <div class="error-msg" id="loginPassErr"></div>
                                </div>

                                <div id="loginRecaptcha" class="mb-3"></div>
                                <div class="error-msg" id="loginCaptchaErr"></div>

                                <button type="submit" class="btn-auth-primary" id="btnLoginSubmit">Sign In</button>

                                <div class="auth-switch">
                                    Don't have an account? <a onclick="toggleAuthMode('register')">Create Account</a>
                                </div>
                                <a class="auth-forgot-link" onclick="toggleForgotPassword(true)">Forgot Password?</a>
                            </form>
                        </div>

                        <div id="forgotView" style="display:none;">
                            <form id="customForgotForm" action="{{ route('public.account.password.email') }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <label>Email Address</label>
                                    <input type="email" name="email" id="forgotEmail" class="form-control"
                                        placeholder="Enter your registered email" required>
                                    <div class="error-msg" id="forgotEmailErr"></div>
                                </div>
                                <p class="text-muted" style="font-size:13px;line-height:1.5;margin-bottom:16px;">
                                    We will email you a new 6-digit PIN. Use that PIN as your password to sign in.
                                </p>
                                <button type="submit" class="btn-auth-primary" id="btnForgotSubmit">Send New PIN</button>
                                <a class="auth-forgot-link" onclick="toggleForgotPassword(false)">← Back to Sign In</a>
                            </form>
                        </div>

                        <div id="forgotSuccessView" class="text-center py-3" style="display:none;">
                            <span style="font-size: 42px; display:block; margin-bottom: 12px;">✉️</span>
                            <h4 class="fw-bold mb-3">PIN Sent!</h4>
                            <p class="text-muted mb-4" id="forgotSuccessMessage">
                                If this email is registered, we have sent a new 6-digit PIN. Use it as your password to sign in.
                            </p>
                            <button type="button" class="btn-auth-primary" id="btnForgotContinue">Continue to Sign In</button>
                        </div>
                    </div>

                    <!-- REGISTER PANEL (MULTI-STEP) -->
                    <div class="auth-panel">
                        <form id="customRegisterForm" action="{{ route('public.account.register') }}" method="POST">
                            @csrf

                            <!-- Step 1: Email -->
                            <div class="reg-step active" id="regStep1">
                                <div class="form-group mb-4">
                                    <label>What is your email address?</label>
                                    <input type="email" name="email" id="regEmail" class="form-control"
                                        placeholder="Enter your email" required>
                                    <div class="error-msg" id="regEmailErr"></div>
                                </div>
                                <button type="button" class="btn-auth-primary" onclick="nextStep(2)">Continue</button>
                                <div class="auth-switch">
                                    Already have an account? <a onclick="toggleAuthMode('login')">Sign In</a>
                                </div>
                            </div>

                            <!-- Step 2: Name -->
                            <div class="reg-step" id="regStep2">
                                <button type="button" class="back-btn" onclick="nextStep(1)">← Back</button>
                                <div class="form-group mb-4">
                                    <label>What is your full name?</label>
                                    <input type="text" name="first_name" id="regName" class="form-control"
                                        placeholder="Enter full name" required>
                                    <div class="error-msg" id="regNameErr"></div>
                                </div>
                                <button type="button" class="btn-auth-primary" onclick="nextStep(3)">Continue</button>
                            </div>

                            <!-- Step 3: Phone -->
                            <div class="reg-step" id="regStep3">
                                <button type="button" class="back-btn" onclick="nextStep(2)">← Back</button>
                                <div class="form-group mb-4">
                                    <label>What is your phone number?</label>
                                    <input type="text" name="phone" id="regPhone" class="form-control"
                                        placeholder="(555) 555-5555" required>
                                    <div class="error-msg" id="regPhoneErr"></div>
                                </div>
                                <button type="button" class="btn-auth-primary" onclick="nextStep(4)">Continue</button>
                            </div>

                            <!-- Step 4: Terms & Conditions -->
                            <div class="reg-step" id="regStep4">
                                <button type="button" class="back-btn" onclick="nextStep(3)">← Back</button>
                                <div class="form-group mb-3">
                                    <label>Terms of Use — MLS®, VOW, CREA, TRREB & PropTX Compliance</label>
                                    <div class="terms-box">
                                        <h5>CREA Compliance</h5>
                                        <p>All listings on this Site are sourced from the Canadian Real Estate Association (CREA) MLS® database. Listings are provided exclusively for personal, non-commercial use by individuals with a bona fide interest in buying, selling, or leasing real estate. Users are prohibited from copying, redistributing, retransmitting, or otherwise using MLS® data outside the scope of evaluating a specific property.</p>

                                        <h5>TRREB Compliance</h5>
                                        <p>The Toronto Regional Real Estate Board (TRREB) maintains proprietary rights and copyrights over all MLS® data in the GTA. You may not scrape, mine, redistribute, or sublicense listing information. TRREB and its authorized representatives may monitor VOW displays to ensure compliance with MLS® rules and policies.</p>

                                        <h5>VOW (Virtual Office Website) Use</h5>
                                        <p>Serik Realty operates as an affiliated VOW partner providing registered users secure access to MLS® listings. Personal information (name, email, phone) may be collected and shared with CREA, TRREB, or PropTX for auditing, verification, or legal purposes. Any agreement creating financial obligations or representation by Serik Realty must be established separately and cannot be accepted merely by using the Site.</p>

                                        <h5>PropTX Compliance</h5>
                                        <p>Some MLS® listings may be accessed through PropTX which owns its MLS® database and system. Users must not scrape, mine, redistribute, or manipulate PropTX listing information. PropTX and authorized representatives may audit the platform to verify compliance.</p>
                                    </div>
                                    <label class="terms-agree">
                                        <input type="checkbox" name="agree_terms_and_policy" id="regTerms" value="1" required>
                                        <span>I have read and agree to the Terms of Use, including MLS®, VOW, CREA, TRREB, and PropTX compliance requirements.</span>
                                    </label>
                                    <div class="error-msg" id="regTermsErr"></div>
                                </div>
                                <div id="registerRecaptcha" class="mb-3"></div>
                                <div class="error-msg" id="registerCaptchaErr"></div>
                                <button type="submit" class="btn-auth-primary" id="btnRegisterSubmit">Create Account</button>
                            </div>

                            <!-- Success Step -->
                            <div class="reg-step text-center py-4" id="regStep5">
                                <span style="font-size: 50px; display:block; margin-bottom: 15px;">🎉</span>
                                <h4 class="fw-bold mb-3">Account Created!</h4>
                                <p class="text-muted mb-4">We have sent a 6-digit PIN to your email address. Click continue to go to the sign-in screen and enter your PIN password.</p>
                                <button type="button" class="btn-auth-primary" id="btnRegContinue">Continue</button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script>
    // Initialize Phone Input if you want international formatting (optional styling)
    const phoneInput = document.querySelector("#regPhone");
    if (phoneInput) {
        window.intlTelInput(phoneInput, {
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js",
            initialCountry: "ca"
        });
    }

    // Unified auth modal helpers
    let authModalInstance = null;

    function getAuthModal() {
        const modalEl = document.getElementById('modalLogin');
        if (!modalEl || typeof bootstrap === 'undefined') {
            return null;
        }

        authModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        return authModalInstance;
    }

    function resetAuthModalState() {
        toggleAuthMode('login');
        toggleForgotPassword(false);
        document.querySelectorAll('.reg-step').forEach(el => el.classList.remove('active'));
        document.getElementById('regStep1')?.classList.add('active');
        document.getElementById('customRegisterForm')?.reset();
        document.getElementById('customForgotForm')?.reset();
        document.getElementById('forgotEmailErr').style.display = 'none';
        document.getElementById('loginPassErr').style.display = 'none';
    }

    function openAuthModal(mode = 'login') {
        toggleAuthMode(mode);
        getAuthModal()?.show();
    }

    function toggleForgotPassword(show) {
        document.getElementById('loginView').style.display = show ? 'none' : 'block';
        document.getElementById('forgotView').style.display = show ? 'block' : 'none';
        document.getElementById('forgotSuccessView').style.display = 'none';

        const title = document.getElementById('authTitle');
        const subtitle = document.getElementById('authSubtitle');

        if (show) {
            title.textContent = 'Forgot Password';
            subtitle.textContent = 'Enter your email to receive a new 6-digit PIN';
        } else {
            title.textContent = 'Welcome Back';
            subtitle.textContent = 'Sign in to unlock exclusive property details';
        }
    }

    // Proxy legacy modal IDs to the unified modal
    document.addEventListener("DOMContentLoaded", function () {
        const modalEl = document.getElementById('modalLogin');
        getAuthModal();

        modalEl?.addEventListener('hidden.bs.modal', resetAuthModalState);

        if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.render === 'function') {
            initSerikRecaptcha();
        }

        document.querySelectorAll('.js-auth-open-login, [href="#modalLogin"], [data-bs-target="#modalLogin"]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                openAuthModal('login');
            });
        });

        document.querySelectorAll('.js-auth-open-register, [href="#modalRegister"], [data-bs-target="#modalRegister"]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                openAuthModal('register');
            });
        });
    });

    // Toggle logic for sliding panel
    function toggleAuthMode(mode) {
        const slider = document.getElementById('authSlider');
        const title = document.getElementById('authTitle');
        const subtitle = document.getElementById('authSubtitle');

        if (mode === 'register') {
            toggleForgotPassword(false);
            slider.classList.add('show-register');
            title.textContent = 'Create an Account';
            subtitle.textContent = 'Join to get full access to sold records & values';
            // reset steps
            document.querySelectorAll('.reg-step').forEach(el => el.classList.remove('active'));
            document.getElementById('regStep1').classList.add('active');
        } else {
            slider.classList.remove('show-register');
            toggleForgotPassword(false);
            title.textContent = 'Welcome Back';
            subtitle.textContent = 'Sign in to unlock exclusive property details';
        }
    }

    function finishRegistrationAndGoToLogin() {
        const form = document.getElementById('customRegisterForm');
        const btn = document.getElementById('btnRegisterSubmit');
        const userEmail = document.getElementById('regEmail')?.value || '';

        toggleForgotPassword(false);
        toggleAuthMode('login');

        document.querySelectorAll('.reg-step').forEach(el => el.classList.remove('active'));
        document.getElementById('regStep1')?.classList.add('active');
        form?.reset();

        if (btn) {
            btn.innerHTML = 'Create Account';
            btn.disabled = false;
        }

        const loginEmailInput = document.querySelector('#customLoginForm input[name="email"]');
        if (loginEmailInput && userEmail) {
            loginEmailInput.value = userEmail;
        }

        const loginPasswordInput = document.querySelector('#customLoginForm input[name="password"]');
        if (loginPasswordInput) {
            loginPasswordInput.focus();
        }
    }

    document.getElementById('btnRegContinue')?.addEventListener('click', finishRegistrationAndGoToLogin);

    document.getElementById('btnForgotContinue')?.addEventListener('click', function () {
        const email = document.getElementById('forgotEmail')?.value || '';
        toggleForgotPassword(false);

        const loginEmailInput = document.querySelector('#customLoginForm input[name="email"]');
        if (loginEmailInput && email) {
            loginEmailInput.value = email;
        }

        document.querySelector('#customLoginForm input[name="password"]')?.focus();
    });

    document.getElementById('customForgotForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        const form = e.target;
        const btn = document.getElementById('btnForgotSubmit');
        const errDiv = document.getElementById('forgotEmailErr');
        const originalText = btn.innerHTML;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
        btn.disabled = true;
        errDiv.style.display = 'none';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(async res => {
                if (res.status === 419) {
                    errDiv.textContent = 'Session expired. Refreshing page...';
                    errDiv.style.display = 'block';
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    setTimeout(() => location.reload(), 600);
                    return;
                }

                let result;
                try {
                    result = await res.json();
                } catch (parseErr) {
                    errDiv.textContent = 'Server error (' + res.status + '). Please refresh the page and try again.';
                    errDiv.style.display = 'block';
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }

                if (!res.ok || result.error) {
                    let errText = result.message || 'Unable to send PIN. Please try again.';
                    if (result.errors) {
                        errText = Object.values(result.errors)[0][0];
                    }
                    errDiv.textContent = errText;
                    errDiv.style.display = 'block';
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return;
                }

                document.getElementById('forgotView').style.display = 'none';
                document.getElementById('forgotSuccessView').style.display = 'block';
                document.getElementById('forgotSuccessMessage').textContent = result.message;
                document.getElementById('authTitle').textContent = 'Check Your Email';
                document.getElementById('authSubtitle').textContent = 'Your new 6-digit PIN has been sent';
                btn.innerHTML = originalText;
                btn.disabled = false;
            })
            .catch(() => {
                errDiv.textContent = 'An unexpected error occurred. Please try again.';
                errDiv.style.display = 'block';
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    });

    let emailExists = false;
    let emailCheckTimer = null;
    let emailCheckRequest = null;

    function checkEmailAvailability(email) {
        const emailField = document.getElementById('regEmail');
        const errDiv = document.getElementById('regEmailErr');

        if (!emailField.checkValidity()) {
            emailExists = false;
            errDiv.style.display = 'none';
            emailField.classList.remove('is-invalid');
            return;
        }

        if (emailCheckRequest) {
            emailCheckRequest.abort();
        }
        emailCheckRequest = new AbortController();

        errDiv.textContent = 'Checking email...';
        errDiv.classList.add('checking');
        errDiv.style.display = 'block';

        fetch(@json(url('/api/v1/check-email')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ email }),
            signal: emailCheckRequest.signal
        })
            .then(res => res.json())
            .then(data => {
                errDiv.classList.remove('checking');
                emailExists = !!data.exists;
                if (data.exists) {
                    errDiv.textContent = data.message || 'This email is already registered.';
                    errDiv.style.display = 'block';
                    emailField.classList.add('is-invalid');
                } else {
                    errDiv.style.display = 'none';
                    emailField.classList.remove('is-invalid');
                }
            })
            .catch(err => {
                if (err.name === 'AbortError') {
                    return;
                }
                errDiv.classList.remove('checking');
                errDiv.style.display = 'none';
            });
    }

    document.getElementById('regEmail').addEventListener('input', function () {
        emailExists = false;
        this.classList.remove('is-invalid');
        clearTimeout(emailCheckTimer);
        const email = this.value.trim();
        if (email.length < 5) {
            document.getElementById('regEmailErr').style.display = 'none';
            return;
        }
        emailCheckTimer = setTimeout(() => checkEmailAvailability(email), 450);
    });

    function nextStep(step) {
        // Basic validation before sliding
        if (step === 2) {
            const emailField = document.getElementById('regEmail');
            const errDiv = document.getElementById('regEmailErr');
            if (!emailField.checkValidity()) {
                emailField.reportValidity();
                return;
            }
            if (emailExists) {
                errDiv.style.display = 'block';
                emailField.classList.add('is-invalid');
                return;
            }
            if (errDiv.classList.contains('checking')) {
                return;
            }
        }
        if (step === 3) {
            const nameField = document.getElementById('regName');
            if (!nameField.checkValidity()) {
                nameField.reportValidity();
                return;
            }
        }
        if (step === 4) {
            const phoneField = document.getElementById('regPhone');
            if (!phoneField.checkValidity()) {
                phoneField.reportValidity();
                return;
            }
        }

        document.querySelectorAll('.reg-step').forEach(el => el.classList.remove('active'));
        document.getElementById('regStep' + step).classList.add('active');

        if (step === 4) {
            ensureRegisterRecaptcha();
        }
    }

    const recaptchaSiteKey = @json(RecaptchaHelper::siteKey());
    let loginRecaptchaWidgetId = null;
    let registerRecaptchaWidgetId = null;

    function initSerikRecaptcha() {
        const loginEl = document.getElementById('loginRecaptcha');
        if (loginEl && loginRecaptchaWidgetId === null && typeof grecaptcha !== 'undefined') {
            loginRecaptchaWidgetId = grecaptcha.render(loginEl, { sitekey: recaptchaSiteKey });
        }
    }
    window.initSerikRecaptcha = initSerikRecaptcha;

    function ensureRegisterRecaptcha() {
        if (typeof grecaptcha === 'undefined') {
            return;
        }

        const registerEl = document.getElementById('registerRecaptcha');
        if (registerEl && registerRecaptchaWidgetId === null) {
            registerRecaptchaWidgetId = grecaptcha.render(registerEl, { sitekey: recaptchaSiteKey });
        }
    }

    function getRecaptchaResponse(widgetId) {
        if (typeof grecaptcha === 'undefined' || widgetId === null) {
            return '';
        }

        return grecaptcha.getResponse(widgetId) || '';
    }

    function resetAuthCaptcha(widgetId = null) {
        if (typeof grecaptcha === 'undefined') {
            return;
        }

        if (widgetId !== null) {
            grecaptcha.reset(widgetId);
            return;
        }

        if (loginRecaptchaWidgetId !== null) {
            grecaptcha.reset(loginRecaptchaWidgetId);
        }

        if (registerRecaptchaWidgetId !== null) {
            grecaptcha.reset(registerRecaptchaWidgetId);
        }
    }

    // Handle AJAX Login Form Submission
    document.getElementById('customLoginForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnLoginSubmit');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing in...';
        btn.disabled = true;

        const errDiv = document.getElementById('loginPassErr');
        const captchaErr = document.getElementById('loginCaptchaErr');
        errDiv.style.display = 'none';
        captchaErr.style.display = 'none';

        const captchaResponse = getRecaptchaResponse(loginRecaptchaWidgetId);
        if (!captchaResponse) {
            captchaErr.textContent = 'Please complete the reCAPTCHA verification.';
            captchaErr.style.display = 'block';
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }

        const formData = new FormData(form);
        formData.set('g-recaptcha-response', captchaResponse);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
            .then(async res => {
                if (res.status === 419) {
                    errDiv.textContent = 'Session expired. Refreshing page...';
                    errDiv.style.display = 'block';
                    setTimeout(() => location.reload(), 600);
                    return;
                }

                const result = await res.json();
                if (!res.ok || result.error) {
                    let errText = result.message || 'Login failed. Please check your credentials.';
                    if (result.errors) {
                        errText = Object.values(result.errors)[0][0]; // get first validation error
                    }
                    errDiv.innerHTML = errText;
                    errDiv.style.display = 'block';
                    resetAuthCaptcha(loginRecaptchaWidgetId);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                } else {
                    location.reload(); // success, reload to view secure property
                }
            })
            .catch(err => {
                errDiv.textContent = 'An unexpected error occurred. Please try again.';
                errDiv.style.display = 'block';
                resetAuthCaptcha(loginRecaptchaWidgetId);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    });

    // Handle AJAX Register Form Submission
    document.getElementById('customRegisterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnRegisterSubmit');
        const originalText = btn.innerHTML;

        const termsField = document.getElementById('regTerms');
        const termsErr = document.getElementById('regTermsErr');
        const captchaErr = document.getElementById('registerCaptchaErr');
        termsErr.style.display = 'none';
        captchaErr.style.display = 'none';

        if (!termsField.checked) {
            termsErr.textContent = 'You must agree to the Terms of Use before creating an account.';
            termsErr.style.display = 'block';
            return;
        }

        const captchaResponse = getRecaptchaResponse(registerRecaptchaWidgetId);
        if (!captchaResponse) {
            captchaErr.textContent = 'Please complete the reCAPTCHA verification.';
            captchaErr.style.display = 'block';
            return;
        }

        if (emailExists) {
            document.getElementById('regEmailErr').textContent = 'This email is already registered. Please sign in instead.';
            document.getElementById('regEmailErr').style.display = 'block';
            nextStep(1);
            return;
        }

        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';
        btn.disabled = true;

        // Setup username from name field
        const formData = new FormData(form);
        formData.set('g-recaptcha-response', captchaResponse);
        const fullName = formData.get('first_name');
        if (fullName) {
            const username = fullName.replace(/\s+/g, '').toLowerCase() + Math.floor(Math.random() * 900 + 100);
            formData.append('username', username);
        }

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(async res => {
                const result = await res.json();
                if (!res.ok || result.error) {
                    let errText = result.message || 'Error occurred';
                    if (result.errors) {
                        errText = Object.values(result.errors)[0][0]; // get first validation error
                    }
                    const errDiv = document.getElementById('regPhoneErr');
                    errDiv.textContent = errText;
                    errDiv.style.display = 'block';
                    resetAuthCaptcha(registerRecaptchaWidgetId);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                } else {
                    const userEmail = document.getElementById('regEmail').value;
                    document.querySelectorAll('.reg-step').forEach(el => el.classList.remove('active'));
                    document.getElementById('regStep5').classList.add('active');

                    const loginEmailInput = document.querySelector('#customLoginForm input[name="email"]');
                    if (loginEmailInput) {
                        loginEmailInput.value = userEmail;
                    }

                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                resetAuthCaptcha(registerRecaptchaWidgetId);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    });
</script>