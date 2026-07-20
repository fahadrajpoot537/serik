<x-core::form :url="route('account.credits.add', $account->id)">
    <x-core::form.text-input
        :label="trans('plugins/real-estate::account.number_of_credits')"
        name="credits"
        type="number"
        value="0"
        :placeholder="trans('plugins/real-estate::account.number_of_credits')"
    />

    <x-core::form.select
        :label="trans('plugins/real-estate::account.action')"
        name="type"
        :options="Botble\RealEstate\Enums\TransactionTypeEnum::labels()"
        :placeholder="trans('plugins/real-estate::account.number_of_credits')"
    />

    <x-core::form.textarea
        :label="trans('plugins/real-estate::account.description')"
        name="description"
        :placeholder="trans('plugins/real-estate::account.description')"
    />
</x-core::form>
