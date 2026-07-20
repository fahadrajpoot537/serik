<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Optimize\Facades\OptimizerHelper;
use Botble\RealEstate\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Invoice;
use Botble\RealEstate\Supports\InvoiceHelper;
use Botble\RealEstate\Tables\AccountInvoiceTable;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        OptimizerHelper::disable();
    }

    public function index(AccountInvoiceTable $accountInvoiceTable)
    {
        $this->pageTitle(trans('plugins/real-estate::invoice.invoices'));

        Theme::breadcrumb()
            ->add(trans('plugins/real-estate::dashboard.header_profile_link'), route('public.account.dashboard'))
            ->add(trans('plugins/real-estate::invoice.manage_invoices'));

        SeoHelper::setTitle(trans('plugins/real-estate::invoice.invoices'));

        return $accountInvoiceTable->render('plugins/real-estate::account.table.base');
    }

    public function show(int|string $id)
    {
        /**
         * @var Invoice $invoice
         */
        $invoice = Invoice::query()->findOrFail($id);

        abort_unless($this->canViewInvoice($invoice), 404);

        $title = trans('plugins/real-estate::invoice.invoice_detail', ['code' => $invoice->code]);

        $this->pageTitle($title);

        SeoHelper::setTitle($title);

        return view('plugins/real-estate::account.dashboard.invoices.show', compact('invoice'));
    }

    public function generate(int|string $id, Request $request, InvoiceHelper $invoiceHelper)
    {
        /**
         * @var Invoice $invoice
         */
        $invoice = Invoice::query()->findOrFail($id);

        abort_unless($this->canViewInvoice($invoice), 404);

        if ($request->input('type') === 'print') {
            return $invoiceHelper->streamInvoice($invoice);
        }

        return $invoiceHelper->downloadInvoice($invoice);
    }

    protected function canViewInvoice(Invoice $invoice): bool
    {
        return auth('account')->id() == $invoice->account_id;
    }
}
