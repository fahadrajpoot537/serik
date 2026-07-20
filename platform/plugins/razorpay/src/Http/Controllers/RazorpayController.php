<?php

namespace Botble\Razorpay\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use Exception;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayController extends BaseController
{
    protected function createRazorpayApi(): Api
    {
        $apiKey = get_payment_setting('key', RAZORPAY_PAYMENT_METHOD_NAME);
        $apiSecret = get_payment_setting('secret', RAZORPAY_PAYMENT_METHOD_NAME);

        return new Api($apiKey, $apiSecret);
    }

    protected function resolveOrderId(
        Request $request,
        string $token,
        ?object $paymentData = null,
        ?array $orderData = null
    ): array|string|null {
        $orderId = $request->input('order_id');

        if ($orderId) {
            return $orderId;
        }

        if (! class_exists(Order::class)) {
            return null;
        }

        if ($paymentData && isset($paymentData->notes)) {
            if (isset($paymentData->notes->order_id)) {
                $orderId = $paymentData->notes->order_id;
                if (is_string($orderId) && str_contains($orderId, ',')) {
                    return explode(',', $orderId);
                }

                return $orderId;
            }

            if (isset($paymentData->notes->order_token)) {
                $orderIds = Order::query()->where('token', $paymentData->notes->order_token)->pluck('id')->all();
                if (! empty($orderIds)) {
                    return $orderIds;
                }
            }
        }

        if ($orderData && isset($orderData['receipt'])) {
            $orderIds = Order::query()->where('token', $orderData['receipt'])->pluck('id')->all();
            if (! empty($orderIds)) {
                PaymentHelper::log(
                    RAZORPAY_PAYMENT_METHOD_NAME,
                    ['order_lookup' => ['receipt' => $orderData['receipt']]],
                    ['found_order_ids' => $orderIds]
                );

                return $orderIds;
            }
        }

        $orderIds = Order::query()->where('token', $token)->pluck('id')->all();
        if (! empty($orderIds)) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['order_lookup_by_token' => ['token' => $token]],
                ['found_order_ids' => $orderIds]
            );

            return $orderIds;
        }

        return null;
    }

    protected function saveOrUpdatePayment(
        string $chargeId,
        array|string|null $orderId,
        float $amount,
        string $currency,
        ?string $status,
        ?int $customerId = null,
        ?string $customerType = null,
        string $context = 'callback'
    ): void {
        if (is_array($orderId) && count($orderId) > 1) {
            $orders = Order::query()->whereIn('id', $orderId)->get();

            foreach ($orders as $order) {
                $orderPaymentData = [
                    'amount' => $order->amount,
                    'currency' => $currency,
                    'charge_id' => $chargeId,
                    'payment_channel' => RAZORPAY_PAYMENT_METHOD_NAME,
                    'status' => $status,
                    'order_id' => $order->id,
                    'customer_id' => $customerId,
                    'customer_type' => $customerType,
                ];

                $existingPayment = Payment::query()
                    ->where('charge_id', $chargeId)
                    ->where('order_id', $order->id)
                    ->where('payment_channel', RAZORPAY_PAYMENT_METHOD_NAME)
                    ->first();

                if (! $existingPayment) {
                    $payment = new Payment();
                    $payment->fill($orderPaymentData);
                    $payment->save();

                    PaymentHelper::log(
                        RAZORPAY_PAYMENT_METHOD_NAME,
                        ['info' => "Payment record created in {$context} for order {$order->id}"],
                        ['charge_id' => $chargeId, 'order_id' => $order->id]
                    );
                } else {
                    $this->updateExistingPayment($existingPayment, $orderPaymentData, $chargeId, $order->id, $status, $context);
                }
            }

            return;
        }

        $paymentData = [
            'amount' => $amount,
            'currency' => $currency,
            'charge_id' => $chargeId,
            'payment_channel' => RAZORPAY_PAYMENT_METHOD_NAME,
            'status' => $status,
            'order_id' => is_array($orderId) ? ($orderId[0] ?? null) : $orderId,
            'customer_id' => $customerId,
            'customer_type' => $customerType,
        ];

        $existingPayment = Payment::query()
            ->where(function ($query) use ($chargeId, $orderId): void {
                $query->where('charge_id', $chargeId)
                    ->orWhere(function ($q) use ($orderId): void {
                        if (! empty($orderId)) {
                            $orderIdValue = is_array($orderId) ? ($orderId[0] ?? null) : $orderId;
                            if ($orderIdValue) {
                                $q->where('order_id', $orderIdValue)
                                    ->where('payment_channel', RAZORPAY_PAYMENT_METHOD_NAME);
                            }
                        }
                    });
            })
            ->first();

        if (! $existingPayment) {
            $payment = new Payment();
            $payment->fill($paymentData);
            $payment->save();

            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['info' => "Payment record created in {$context}"],
                ['charge_id' => $chargeId, 'order_id' => $orderId]
            );

            return;
        }

        $this->updateExistingPayment($existingPayment, $paymentData, $chargeId, $orderId, $status, $context);
    }

    protected function updateExistingPayment(
        Payment $existingPayment,
        array $paymentData,
        string $chargeId,
        array|string|null $orderId,
        ?string $status,
        string $context
    ): void {

        $updated = false;

        if ($existingPayment->amount == 0 && $paymentData['amount'] > 0) {
            $existingPayment->amount = $paymentData['amount'];
            $updated = true;
        }

        if ($existingPayment->currency !== $paymentData['currency']) {
            $existingPayment->currency = $paymentData['currency'];
            $updated = true;
        }

        if ($existingPayment->status !== $status) {
            $existingPayment->status = $status;
            $updated = true;
        }

        if (! $existingPayment->order_id && $paymentData['order_id']) {
            $existingPayment->order_id = $paymentData['order_id'];
            $updated = true;
        }

        if (! $existingPayment->customer_id && $paymentData['customer_id']) {
            $existingPayment->customer_id = $paymentData['customer_id'];
            $existingPayment->customer_type = $paymentData['customer_type'];
            $updated = true;
        }

        if ($updated) {
            $existingPayment->save();

            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['info' => "Payment record updated in {$context}"],
                ['charge_id' => $chargeId, 'order_id' => $orderId, 'status' => $status, 'amount' => $paymentData['amount']]
            );
        }
    }

    protected function finalizeOrders(array|string|null $orderId, string $chargeId): void
    {
        if (empty($orderId) || ! class_exists(Order::class)) {
            return;
        }

        $orders = Order::query()->whereIn('id', (array) $orderId)->where('is_finished', false)->get();

        if ($orders->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            $order->is_finished = true;
            $order->save();

            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['info' => 'Marked order as finished'],
                ['order_id' => $order->id]
            );
        }

        if (class_exists(OrderHelper::class)) {
            OrderHelper::processOrder($orders->pluck('id')->all(), $chargeId);
        }
    }

    protected function linkPaymentWithOrder($chargeId, $orderId, $status = null): void
    {
        if (! $chargeId || ! $orderId) {
            return;
        }

        $payment = Payment::query()
            ->where('charge_id', $chargeId)
            ->whereNull('order_id')
            ->first();

        if (! $payment) {
            return;
        }

        $payment->order_id = is_array($orderId) ? ($orderId[0] ?? null) : $orderId;

        if ($status && $payment->status == PaymentStatusEnum::PENDING) {
            $payment->status = $status;
        }

        $payment->save();

        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['info' => 'Linked orphaned payment with order and updated status'],
            ['charge_id' => $chargeId, 'order_id' => $orderId, 'status' => $status]
        );
    }

    protected function handleErrorResponse(
        BaseHttpResponse $response,
        string $token,
        string $message
    ): BaseHttpResponse {
        return $response
            ->setNextUrl(PaymentHelper::getCancelURL($token) . '&error_message=' . urlencode($message))
            ->withInput()
            ->setMessage($message);
    }

    protected function processVerifiedPayment(
        Request $request,
        string $token,
        string $chargeId,
        string $razorpayOrderId,
        string $signature
    ): array {
        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['verification_attempt' => ['payment_id' => $chargeId, 'order_id' => $razorpayOrderId]],
            ['token' => $token]
        );

        $api = $this->createRazorpayApi();

        // @phpstan-ignore-next-line
        $api->utility->verifyPaymentSignature([
            'razorpay_signature' => $signature,
            'razorpay_payment_id' => $chargeId,
            'razorpay_order_id' => $razorpayOrderId,
        ]);

        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['signature_verification' => 'success'],
            ['payment_id' => $chargeId, 'order_id' => $razorpayOrderId]
        );

        do_action('payment_before_making_api_request', RAZORPAY_PAYMENT_METHOD_NAME, ['order_id' => $razorpayOrderId]);

        // @phpstan-ignore-next-line
        $order = $api->order->fetch($razorpayOrderId);
        $orderData = $order->toArray();

        do_action('payment_after_api_response', RAZORPAY_PAYMENT_METHOD_NAME, ['order_id' => $razorpayOrderId], $orderData);

        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['order_details' => ['order_id' => $razorpayOrderId]],
            ['order_data' => $orderData]
        );

        $amount = $orderData['amount_paid'] / 100;
        $currency = $orderData['currency'];
        $status = $orderData['status'] === 'paid' ? PaymentStatusEnum::COMPLETED : PaymentStatusEnum::PENDING;
        $orderId = $this->resolveOrderId($request, $token, null, $orderData);

        if (empty($orderId)) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['warning' => 'No order found for payment, but processing anyway'],
                [
                    'payment_id' => $chargeId,
                    'receipt' => $orderData['receipt'] ?? null,
                    'token' => $token,
                ]
            );

            if (class_exists(Order::class)) {
                $sessionOrder = Order::query()
                    ->where('token', $token)
                    ->where('is_finished', false)
                    ->first();

                if ($sessionOrder) {
                    $orderId = [$sessionOrder->id];
                    PaymentHelper::log(
                        RAZORPAY_PAYMENT_METHOD_NAME,
                        ['info' => 'Found incomplete order from session'],
                        ['order_id' => $orderId]
                    );

                    $this->linkPaymentWithOrder($chargeId, $orderId, PaymentStatusEnum::COMPLETED);
                }
            }
        }

        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['processing_payment' => ['amount' => $amount, 'currency' => $currency, 'status' => $status]],
            ['charge_id' => $chargeId, 'order_id' => $orderId]
        );

        return [$amount, $currency, $status, $orderId];
    }

    protected function processUnverifiedPayment(
        Request $request,
        string $token,
        string $chargeId,
        ?string $razorpayOrderId,
        ?string $signature
    ): array {
        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['warning' => 'Missing order ID or signature, attempting direct payment fetch'],
            [
                'token' => $token,
                'has_order_id' => (bool) $razorpayOrderId,
                'has_signature' => (bool) $signature,
                'payment_id' => $chargeId,
            ]
        );

        try {
            $api = $this->createRazorpayApi();
            // @phpstan-ignore-next-line
            $payment = $api->payment->fetch($chargeId);

            if ($payment && in_array($payment->status, ['captured', 'authorized'])) {
                $amount = $payment->amount / 100;
                $currency = $payment->currency;
                $status = PaymentStatusEnum::COMPLETED;
                $orderId = $this->resolveOrderId($request, $token, $payment);

                PaymentHelper::log(
                    RAZORPAY_PAYMENT_METHOD_NAME,
                    ['payment_processed_without_signature' => true],
                    ['charge_id' => $chargeId, 'status' => $payment->status]
                );

                return [$amount, $currency, $status, $orderId];
            }
        } catch (Exception $e) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['error' => 'Failed to fetch payment details'],
                ['exception' => $e->getMessage(), 'payment_id' => $chargeId]
            );
            BaseHelper::logError($e);
        }

        return [0, 'USD', PaymentStatusEnum::PENDING, null];
    }

    protected function attemptFallbackPaymentProcessing(string $chargeId): bool
    {
        try {
            $api = $this->createRazorpayApi();
            // @phpstan-ignore-next-line
            $payment = $api->payment->fetch($chargeId);

            if ($payment && $payment->status === 'captured') {
                return true;
            }
        } catch (Exception $e) {
            BaseHelper::logError($e);
        }

        return false;
    }

    protected function verifyWebhookSignature(string $content, string $signature, string $webhookSecret): bool
    {
        $expectedSignature = hash_hmac('sha256', $content, $webhookSecret);

        if (hash_equals($expectedSignature, $signature)) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['webhook_signature_verification' => 'success'],
                ['signature' => substr($signature, 0, 10) . '...']
            );

            return true;
        }

        return false;
    }

    /**
     * @phpstan-return string
     */
    protected function determinePaymentStatus(array $paymentEntity, array $orderData, Api $api, string $chargeId): string
    {
        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['payment_status_check' => [
                'payment_status' => $paymentEntity['status'] ?? 'unknown',
                'order_status' => $orderData['status'] ?? 'unknown',
            ]],
            ['charge_id' => $chargeId]
        );

        if (in_array($paymentEntity['status'], ['captured', 'authorized']) || $orderData['status'] === 'paid') {
            if ($paymentEntity['status'] === 'authorized' && empty($paymentEntity['captured'])) {
                try {
                    // @phpstan-ignore-next-line
                    $api->payment->capture($chargeId, ['amount' => $paymentEntity['amount']]);
                    PaymentHelper::log(
                        RAZORPAY_PAYMENT_METHOD_NAME,
                        ['payment_captured' => true],
                        ['charge_id' => $chargeId]
                    );
                } catch (Exception $e) {
                    PaymentHelper::log(
                        RAZORPAY_PAYMENT_METHOD_NAME,
                        ['capture_error' => $e->getMessage()],
                        ['charge_id' => $chargeId]
                    );
                }
            }

            return PaymentStatusEnum::COMPLETED;
        }

        if ($paymentEntity['status'] === 'failed') {
            return PaymentStatusEnum::FAILED;
        }

        if ($paymentEntity['status'] === 'refunded') {
            return PaymentStatusEnum::REFUNDED;
        }

        return PaymentStatusEnum::PENDING;
    }

    protected function resolveOrderIdFromWebhook(
        array $paymentEntity,
        array $orderData,
        ?int $existingPaymentOrderId = null
    ): array|string|null {
        if ($existingPaymentOrderId) {
            return $existingPaymentOrderId;
        }

        if (! class_exists(Order::class)) {
            return null;
        }

        $orderId = Order::query()->where('token', $orderData['receipt'])->pluck('id')->all();

        if (! empty($orderId)) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['order_lookup_by_receipt' => ['receipt' => $orderData['receipt'], 'found_order_ids' => $orderId]],
                ['method' => 'webhook']
            );

            return $orderId;
        }

        if (isset($paymentEntity['notes']['order_token'])) {
            return Order::query()->where('token', $paymentEntity['notes']['order_token'])->pluck('id')->all();
        }

        if (isset($paymentEntity['notes']['order_id'])) {
            $orderIdFromNotes = $paymentEntity['notes']['order_id'];
            if (is_string($orderIdFromNotes) && str_contains($orderIdFromNotes, ',')) {
                return explode(',', $orderIdFromNotes);
            }

            return (array) $orderIdFromNotes;
        }

        return null;
    }

    protected function getCustomerInfoFromOrder(array|string|null $orderId): array
    {
        if (empty($orderId) || ! class_exists(Order::class)) {
            return [];
        }

        $order = Order::query()->whereIn('id', (array) $orderId)->first();

        if ($order && $order->user_id) {
            return [
                'customer_id' => $order->user_id,
                'customer_type' => $order->user_type,
            ];
        }

        return [];
    }

    protected function handlePaymentWebhookEvent(Request $request, Api $api)
    {
        try {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['event_type' => $request->input('event')],
                ['payload' => $request->input('payload')]
            );

            $paymentEntity = $request->input('payload.payment.entity');
            if (! $paymentEntity) {
                return response('No payment entity found', 400);
            }

            $chargeId = $paymentEntity['id'];
            $razorpayOrderId = $paymentEntity['order_id'];

            if (! $razorpayOrderId) {
                return response('No order ID found', 400);
            }

            do_action('payment_before_making_api_request', RAZORPAY_PAYMENT_METHOD_NAME, ['order_id' => $razorpayOrderId]);

            // @phpstan-ignore-next-line
            $order = $api->order->fetch($razorpayOrderId);
            $orderData = $order->toArray();

            do_action('payment_after_api_response', RAZORPAY_PAYMENT_METHOD_NAME, ['order_id' => $razorpayOrderId], $orderData);

            $status = $this->determinePaymentStatus($paymentEntity, $orderData, $api, $chargeId);

            $existingPayment = Payment::query()->where('charge_id', $chargeId)->first();
            $existingPaymentOrderId = $existingPayment?->order_id;

            if ($existingPayment) {
                $existingPayment->status = $status;
                $existingPayment->save();
            }

            $orderId = $this->resolveOrderIdFromWebhook($paymentEntity, $orderData, $existingPaymentOrderId);
            $amount = isset($orderData['amount_paid']) ? $orderData['amount_paid'] / 100 : ($paymentEntity['amount'] / 100);
            $currency = $orderData['currency'] ?? $paymentEntity['currency'];
            $customerInfo = $this->getCustomerInfoFromOrder($orderId);

            if ($orderId) {
                $this->linkPaymentWithOrder($chargeId, $orderId, $status);
            }

            $this->saveOrUpdatePayment(
                $chargeId,
                $orderId,
                $amount,
                $currency,
                $status,
                $customerInfo['customer_id'] ?? null,
                $customerInfo['customer_type'] ?? null,
                'webhook'
            );

            if ($status == PaymentStatusEnum::COMPLETED) {
                $this->finalizeOrders($orderId, $chargeId);
            }

            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['webhook_payment_processed' => true],
                ['charge_id' => $chargeId, 'order_id' => $orderId, 'status' => $status]
            );

            return response('Webhook processed successfully');
        } catch (BadRequestError $exception) {
            BaseHelper::logError($exception);

            return response('Error processing payment: ' . $exception->getMessage(), 400);
        }
    }

    public function callback(
        string $token,
        Request $request,
        BaseHttpResponse $response
    ): BaseHttpResponse {
        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['callback_request' => $request->all()],
            ['token' => $token, 'headers' => $request->headers->all()]
        );

        if ($errorDescription = $request->input('error.description')) {
            $message = $request->input('error.code') . ': ' . $errorDescription;

            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['error' => $request->input('error')],
                ['token' => $token, 'message' => $message]
            );

            return $this->handleErrorResponse($response, $token, $message);
        }

        $chargeId = $request->input('razorpay_payment_id');

        if (! $chargeId) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['error' => 'Missing payment ID'],
                ['token' => $token]
            );

            return $response
                ->setNextUrl(PaymentHelper::getCancelURL($token))
                ->withInput()
                ->setMessage(trans('plugins/razorpay::razorpay.payment_failed'));
        }

        $razorpayOrderId = $request->input('razorpay_order_id');
        $signature = $request->input('razorpay_signature');

        try {
            if ($razorpayOrderId && $signature) {
                [$amount, $currency, $status, $orderId] = $this->processVerifiedPayment(
                    $request,
                    $token,
                    $chargeId,
                    $razorpayOrderId,
                    $signature
                );
            } else {
                [$amount, $currency, $status, $orderId] = $this->processUnverifiedPayment(
                    $request,
                    $token,
                    $chargeId,
                    $razorpayOrderId,
                    $signature
                );
            }
        } catch (SignatureVerificationError $exception) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['error' => 'Signature verification failed'],
                [
                    'exception' => $exception->getMessage(),
                    'payment_id' => $chargeId,
                    'order_id' => $razorpayOrderId,
                ]
            );

            BaseHelper::logError($exception);

            return $this->handleErrorResponse($response, $token, $exception->getMessage());
        } catch (Exception $exception) {
            PaymentHelper::log(
                RAZORPAY_PAYMENT_METHOD_NAME,
                ['error' => 'Payment processing failed'],
                [
                    'exception' => $exception->getMessage(),
                    'payment_id' => $chargeId,
                    'token' => $token,
                ]
            );

            BaseHelper::logError($exception);

            $fallbackResult = $this->attemptFallbackPaymentProcessing($chargeId);

            if ($fallbackResult) {
                return $response
                    ->setNextUrl(PaymentHelper::getRedirectURL($token) . '?charge_id=' . $chargeId)
                    ->setMessage(trans('plugins/payment::payment.checkout_success'));
            }

            return $this->handleErrorResponse($response, $token, trans('plugins/razorpay::razorpay.payment_failed'));
        }

        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['success' => 'Payment processed successfully'],
            ['charge_id' => $chargeId, 'token' => $token]
        );

        $customerInfo = $this->getCustomerInfoFromOrder($orderId);

        $this->saveOrUpdatePayment(
            $chargeId,
            $orderId,
            $amount,
            $currency,
            $status,
            $customerInfo['customer_id'] ?? null,
            $customerInfo['customer_type'] ?? null
        );

        if ($status == PaymentStatusEnum::COMPLETED) {
            $this->finalizeOrders($orderId, $chargeId);
        }

        $this->linkPaymentWithOrder($chargeId, $orderId, $status);

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL($token) . '?charge_id=' . $chargeId)
            ->setMessage(trans('plugins/payment::payment.checkout_success'));
    }

    public function webhook(Request $request)
    {
        $webhookSecret = get_payment_setting('webhook_secret', RAZORPAY_PAYMENT_METHOD_NAME);
        $signature = $request->header('X-Razorpay-Signature');
        $content = $request->getContent();

        PaymentHelper::log(
            RAZORPAY_PAYMENT_METHOD_NAME,
            ['webhook_request' => $request->all()],
            ['headers' => $request->headers->all()]
        );

        if ($webhookSecret && $signature && $content) {
            try {
                if (! $this->verifyWebhookSignature($content, $signature, $webhookSecret)) {
                    BaseHelper::logError(new Exception('Invalid webhook signature'));

                    return response('Invalid signature', 400);
                }
            } catch (Exception $exception) {
                BaseHelper::logError($exception);

                return response('Error verifying webhook signature: ' . $exception->getMessage(), 400);
            }
        } else {
            if (! $webhookSecret) {
                PaymentHelper::log(
                    RAZORPAY_PAYMENT_METHOD_NAME,
                    ['webhook_warning' => 'No webhook secret configured'],
                    ['recommendation' => 'Configure webhook secret for secure webhook verification']
                );
            }

            if ($signature && ! $webhookSecret) {
                PaymentHelper::log(
                    RAZORPAY_PAYMENT_METHOD_NAME,
                    ['webhook_warning' => 'Signature provided but no webhook secret configured'],
                    ['signature_prefix' => substr($signature, 0, 10) . '...']
                );
            }
        }

        try {
            $event = $request->input('event');
            $eventId = $request->header('X-Razorpay-Event-Id');

            if ($eventId) {
                PaymentHelper::log(
                    RAZORPAY_PAYMENT_METHOD_NAME,
                    ['webhook_event_id' => $eventId],
                    ['event' => $event]
                );
            }

            $api = $this->createRazorpayApi();

            switch ($event) {
                case 'payment.authorized':
                case 'payment.captured':
                case 'payment.failed':
                case 'payment.pending':
                case 'order.paid':
                    return $this->handlePaymentWebhookEvent($request, $api);

                case 'refund.created':
                    return response('Refund event received');

                default:
                    PaymentHelper::log(
                        RAZORPAY_PAYMENT_METHOD_NAME,
                        ['unhandled_event' => $event],
                        ['payload' => $request->all()]
                    );

                    return response('Event type not handled: ' . $event);
            }
        } catch (Exception $exception) {
            BaseHelper::logError($exception);

            return response('Error processing webhook: ' . $exception->getMessage(), 500);
        }
    }
}
