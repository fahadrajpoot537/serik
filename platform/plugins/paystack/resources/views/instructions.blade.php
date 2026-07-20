<ol>
    <li>
        <p>
            <a
                href="https://paystack.com"
                target="_blank"
            >
                {{ trans('plugins/paystack::paystack.register_account', ['name' => 'Paystack']) }}
            </a>
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/paystack::paystack.after_registration', ['name' => 'Paystack']) }}
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/paystack::paystack.enter_keys') }}
        </p>
    </li>
</ol>
