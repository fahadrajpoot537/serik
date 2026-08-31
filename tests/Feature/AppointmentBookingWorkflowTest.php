<?php

namespace Tests\Feature;

use App\Exceptions\AppointmentWorkflowException;
use App\Jobs\ProcessAppointmentBookingJob;
use App\Mail\AppointmentClientConfirmation;
use App\Mail\AppointmentTeamNotification;
use App\Models\AppointmentBooking;
use App\Services\Appointments\AppointmentBookingOrchestrator;
use App\Services\Appointments\AppointmentMailer;
use App\Support\AppointmentScheduler;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentBookingWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'serik.appointment.timezone' => 'America/Toronto',
            'serik.appointment.max_year' => 2030,
            'serik.appointment.process_sync' => true,
            'serik.appointment.calendar_id' => 'cal_test_1',
            'serik.appointment.notify_email' => 'agent@example.com',
            'serik.appointment.source' => 'Serik.ca - Appointment',
            'serik.appointment.inquiry_type_field_id' => 'field_inquiry',
            'gohighlevel.contact_forms.lead_source_field_id' => 'field_source',
            'services.gohighlevel.enabled' => true,
            'services.gohighlevel.api_token' => 'ghl-secret-token-xyz',
            'services.gohighlevel.location_id' => 'loc_1',
            'services.gohighlevel.base_url' => 'https://services.leadconnectorhq.com',
            'logging.channels.appointments.driver' => 'errorlog',
        ]);
        $this->createTables();
    }

    public function test_complete_success_creates_one_local_appointment_and_integrations(): void
    {
        Mail::fake();
        $this->fakeGhl();

        $response = AppointmentScheduler::book($this->request());
        $payload = $response->getData(true);

        $this->assertTrue($payload['status'] === true);
        $this->assertSame('Appointment booked successfully', $payload['message']);
        $this->assertSame(AppointmentBooking::STATUS_CONFIRMED, $payload['state']);
        $this->assertSame(1, AppointmentBooking::query()->count());
        $booking = AppointmentBooking::query()->first();
        $this->assertSame('ghl_contact_1', $booking->ghl_contact_id);
        $this->assertSame('cal_evt_1', $booking->calendar_event_id);
        $this->assertSame('Serik.ca - Appointment', $booking->source);
        $this->assertNotNull($booking->confirmed_at);
        $this->assertTrue($booking->stepDone(AppointmentBooking::STEP_GHL));
        $this->assertTrue($booking->stepDone(AppointmentBooking::STEP_CALENDAR));
        $this->assertTrue($booking->stepDone(AppointmentBooking::STEP_CLIENT_MAIL));
        $this->assertTrue($booking->stepDone(AppointmentBooking::STEP_TEAM_MAIL));

        Mail::assertSent(AppointmentClientConfirmation::class, 1);
        Mail::assertSent(AppointmentTeamNotification::class, 1);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'contacts/upsert')
                && $request['source'] === 'Serik.ca - Appointment'
                && in_array('Appointment', $request['tags'] ?? [], true);
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'calendars/events/appointments');
        });
    }

    public function test_queued_processing_returns_pending_not_success(): void
    {
        Queue::fake();
        Mail::fake();
        $this->fakeGhl();
        config(['serik.appointment.process_sync' => false]);

        $response = AppointmentScheduler::book($this->request());
        $payload = $response->getData(true);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertNotTrue($payload['status'] === true);
        $this->assertTrue($payload['pending'] ?? false);
        $this->assertStringContainsString('confirming your appointment', $payload['message']);
        $this->assertSame(1, AppointmentBooking::query()->count());
        $this->assertNull(AppointmentBooking::query()->first()->confirmed_at);
        Queue::assertPushed(ProcessAppointmentBookingJob::class, 1);
        Mail::assertNothingSent();
    }

    public function test_ghl_failure_never_displays_success(): void
    {
        Mail::fake();
        $this->fakeGhl(['upsert' => Http::response(['message' => 'unauthorized'], 401)]);

        $response = AppointmentScheduler::book($this->request());
        $payload = $response->getData(true);

        $this->assertNotTrue($payload['status'] === true);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(AppointmentBooking::STATUS_FAILED, AppointmentBooking::query()->first()->status);
        $this->assertSame('ghl_contact', AppointmentBooking::query()->first()->failed_step);
        Mail::assertNothingSent();
    }

    public function test_calendar_failure_never_displays_success(): void
    {
        Mail::fake();
        $this->fakeGhl(['calendar' => Http::response(['message' => 'conflict'], 409)]);

        $response = AppointmentScheduler::book($this->request());
        $payload = $response->getData(true);

        $this->assertNotTrue($payload['status'] === true);
        $booking = AppointmentBooking::query()->first();
        $this->assertSame('ghl_contact_1', $booking->ghl_contact_id);
        $this->assertSame('calendar', $booking->failed_step);
        $this->assertNull($booking->confirmed_at);
        Mail::assertNothingSent();
    }

    public function test_client_email_failure_never_displays_success(): void
    {
        $this->fakeGhl();
        $this->mock(AppointmentMailer::class, function ($mock) {
            $mock->shouldReceive('sendClient')->once()->andThrow(new AppointmentWorkflowException(
                AppointmentWorkflowException::CLIENT_EMAIL,
                'client_email',
                false,
                'Client confirmation was not accepted.'
            ));
            $mock->shouldReceive('sendTeam')->never();
        });

        $response = AppointmentScheduler::book($this->request());
        $this->assertNotTrue($response->getData(true)['status'] === true);
        $booking = AppointmentBooking::query()->first();
        $this->assertSame('client_email', $booking->failed_step);
        $this->assertSame('ghl_contact_1', $booking->ghl_contact_id);
        $this->assertSame('cal_evt_1', $booking->calendar_event_id);
    }

    public function test_team_email_failure_never_displays_success(): void
    {
        Mail::fake();
        Queue::fake();
        $this->fakeGhl();
        $this->mock(AppointmentMailer::class, function ($mock) {
            $mock->shouldReceive('sendClient')->once()->andReturn('accepted');
            $mock->shouldReceive('sendTeam')->once()->andThrow(new AppointmentWorkflowException(
                AppointmentWorkflowException::TEAM_EMAIL,
                'team_email',
                true,
                'Team notification was not accepted.'
            ));
        });

        $response = AppointmentScheduler::book($this->request());
        $payload = $response->getData(true);
        $this->assertNotTrue($payload['status'] === true);
        $this->assertSame(202, $response->getStatusCode());
        $this->assertNull(AppointmentBooking::query()->first()->confirmed_at);
    }

    public function test_missing_assigned_recipient_never_displays_success(): void
    {
        Mail::fake();
        $this->fakeGhl();
        config(['serik.appointment.notify_email' => 'not-an-email']);

        $response = AppointmentScheduler::book($this->request());
        $this->assertNotTrue($response->getData(true)['status'] === true);
        $this->assertSame('team_email', AppointmentBooking::query()->first()->failed_step);
        $this->assertSame(AppointmentWorkflowException::MISSING_RECIPIENT, AppointmentBooking::query()->first()->error_code);
    }

    public function test_queue_stall_never_displays_success(): void
    {
        Queue::fake();
        Mail::fake();
        $this->fakeGhl();
        config(['serik.appointment.process_sync' => false]);

        $book = AppointmentScheduler::book($this->request());
        $this->assertNotTrue($book->getData(true)['status'] === true);

        $status = AppointmentScheduler::statusResponse($this->statusRequest($book->getData(true)['token']));
        $this->assertNotTrue($status->getData(true)['status'] === true);
        $this->assertContains($status->getData(true)['state'], ['pending', 'processing']);
    }

    public function test_ghl_retry_does_not_duplicate_contact(): void
    {
        Mail::fake();
        Queue::fake();
        Http::fake([
            '*/contacts/upsert' => Http::sequence()
                ->push(['message' => 'timeout'], 500)
                ->push(['contact' => ['id' => 'ghl_contact_1']], 200),
            '*/calendars/events/appointments' => Http::response(['id' => 'cal_evt_1'], 200),
            '*/notes' => Http::response(['id' => 'n1'], 201),
            '*' => Http::response(['contacts' => []], 200),
        ]);

        $first = AppointmentScheduler::book($this->request());
        $this->assertNotTrue($first->getData(true)['status'] === true);
        $booking = AppointmentBooking::query()->first();
        $this->assertNull($booking->ghl_contact_id);

        app(AppointmentBookingOrchestrator::class)->run($booking->fresh());
        $booking->refresh();
        $this->assertSame('ghl_contact_1', $booking->ghl_contact_id);
        $this->assertTrue($booking->isConfirmed());
        $this->assertSame(1, AppointmentBooking::query()->count());

        $upserts = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'contacts/upsert'));
        $this->assertSame(2, $upserts->count());
    }

    public function test_calendar_and_email_retries_do_not_duplicate_completed_work(): void
    {
        Mail::fake();
        Queue::fake();
        Http::fake([
            '*/contacts/upsert' => Http::response(['contact' => ['id' => 'ghl_contact_1']], 200),
            '*/calendars/events/appointments' => Http::sequence()
                ->push(['message' => 'timeout'], 500)
                ->push(['id' => 'cal_evt_1'], 200),
            '*/notes' => Http::response(['id' => 'n1'], 201),
            '*' => Http::response(['contacts' => []], 200),
        ]);

        $first = AppointmentScheduler::book($this->request());
        $this->assertNotTrue($first->getData(true)['status'] === true);
        $booking = AppointmentBooking::query()->first();
        $this->assertSame('ghl_contact_1', $booking->ghl_contact_id);
        $this->assertNull($booking->calendar_event_id);

        app(AppointmentBookingOrchestrator::class)->run($booking->fresh());
        $booking->refresh();
        $this->assertTrue($booking->isConfirmed());
        $this->assertSame('cal_evt_1', $booking->calendar_event_id);

        app(AppointmentBookingOrchestrator::class)->run($booking->fresh());
        Mail::assertSent(AppointmentClientConfirmation::class, 1);
        Mail::assertSent(AppointmentTeamNotification::class, 1);
        $calendarCalls = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'calendars/events'));
        $this->assertSame(2, $calendarCalls->count());
    }

    public function test_double_click_creates_one_booking(): void
    {
        Mail::fake();
        $this->fakeGhl();

        $first = AppointmentScheduler::book($this->request());
        $second = AppointmentScheduler::book($this->request());

        $this->assertTrue($first->getData(true)['status'] === true);
        $this->assertTrue($second->getData(true)['status'] === true);
        $this->assertSame(1, AppointmentBooking::query()->count());
        $this->assertSame($first->getData(true)['booking_reference'], $second->getData(true)['booking_reference']);
        Mail::assertSent(AppointmentClientConfirmation::class, 1);
    }

    public function test_status_endpoint_rejects_invalid_expired_and_foreign_tokens(): void
    {
        Mail::fake();
        $this->fakeGhl();
        $booked = AppointmentScheduler::book($this->request());
        $token = $booked->getData(true)['token'];

        $ok = AppointmentScheduler::statusResponse($this->statusRequest($token));
        $this->assertTrue($ok->getData(true)['status'] === true);
        $this->assertArrayNotHasKey('email', $ok->getData(true));
        $this->assertArrayNotHasKey('phone', $ok->getData(true));
        $this->assertArrayNotHasKey('ghl_contact_id', $ok->getData(true));

        $invalid = AppointmentScheduler::statusResponse($this->statusRequest(str_repeat('a', 48)));
        $this->assertSame(404, $invalid->getStatusCode());
        $this->assertNotTrue($invalid->getData(true)['status'] === true);

        $other = AppointmentBooking::query()->create([
            'public_token' => AppointmentBooking::makePublicToken(),
            'booking_reference' => 'SRK-OTHER01',
            'idempotency_key' => hash('sha256', 'other'),
            'slot_key' => '2026-09-03|9:30',
            'status' => AppointmentBooking::STATUS_CONFIRMED,
            'name' => 'Other Person',
            'email' => 'other@example.com',
            'phone' => '+14165550999',
            'consultation_type' => 'Buyer',
            'appointment_date' => '2026-09-03',
            'appointment_time' => '9:30',
            'timezone' => 'America/Toronto',
            'source' => 'Serik.ca - Appointment',
        ]);
        $foreign = AppointmentScheduler::statusResponse($this->statusRequest($other->public_token));
        $this->assertNotSame('other@example.com', $foreign->getData(true)['email'] ?? null);
        $this->assertArrayNotHasKey('email', $foreign->getData(true));

        $expired = AppointmentBooking::query()->create([
            'public_token' => AppointmentBooking::makePublicToken(),
            'booking_reference' => 'SRK-OLD0001',
            'idempotency_key' => hash('sha256', 'old'),
            'slot_key' => 'released:old',
            'status' => AppointmentBooking::STATUS_PENDING,
            'name' => 'Old',
            'email' => 'old@example.com',
            'phone' => '+14165550888',
            'consultation_type' => 'Buyer',
            'appointment_date' => '2026-09-04',
            'appointment_time' => '10:30',
            'timezone' => 'America/Toronto',
            'source' => 'Serik.ca - Appointment',
        ]);
        $expired->timestamps = false;
        $expired->created_at = now()->subDays(10);
        $expired->updated_at = now()->subDays(10);
        $expired->save();
        $gone = AppointmentScheduler::statusResponse($this->statusRequest($expired->public_token));
        $this->assertSame(410, $gone->getStatusCode());
    }

    public function test_conversion_analytics_and_form_contract(): void
    {
        $blade = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/property-categories/styles/style-2.blade.php'));
        $this->assertStringContainsString('Appointment booked successfully', $blade);
        $this->assertStringContainsString('confirming your appointment', $blade);
        $this->assertStringContainsString("event: \"appointment_booked\"", $blade);
        $this->assertStringContainsString('fireConfirmedAnalytics', $blade);
        $this->assertStringContainsString('sessionStorage', $blade);
        $this->assertDoesNotMatchRegularExpression('/<form[^>]+method=["\']post["\'][^>]*id=["\']appointmentForm["\']/i', $blade);
        $this->assertStringContainsString('<form class="dark-form" id="appointmentForm">', $blade);
        $this->assertStringContainsString('dataLayer.push', $blade);
    }

    public function test_logs_do_not_contain_secrets_or_raw_personal_data(): void
    {
        Mail::fake();
        $this->fakeGhl();
        $records = [];
        Log::listen(function ($event) use (&$records) {
            $records[] = $event->message . ' ' . json_encode($event->context);
        });

        AppointmentScheduler::book($this->request());
        $joined = implode("\n", $records);
        $this->assertStringNotContainsString('ghl-secret-token-xyz', $joined);
        $this->assertStringNotContainsString('Bearer ', $joined);
        $this->assertStringNotContainsString('jane@example.com', $joined);
    }

    public function test_existing_date_time_type_and_ampm_validation_still_pass(): void
    {
        $now = Carbon::parse('2026-08-31 10:00:00', 'America/Toronto');
        $ok = AppointmentScheduler::validateBooking($this->payload(), $now, []);
        $this->assertTrue($ok['ok']);
        $this->assertSame('9:30 AM', AppointmentScheduler::formatDisplay(9, 30));
        $this->assertSame('1:30 PM', AppointmentScheduler::formatDisplay(13, 30));
        $this->assertSame('+14165550100', $ok['phone']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeGhl(array $overrides = []): void
    {
        Http::fake([
            '*/contacts/upsert' => $overrides['upsert'] ?? Http::response(['contact' => ['id' => 'ghl_contact_1']], 200),
            '*/calendars/events/appointments' => $overrides['calendar'] ?? Http::response(['id' => 'cal_evt_1'], 200),
            '*/notes' => $overrides['notes'] ?? Http::response(['id' => 'note_1'], 201),
            '*' => $overrides['search'] ?? Http::response(['contacts' => []], 200),
        ]);
    }

    private function request(array $overrides = []): Request
    {
        return Request::create('/api/v1/book-appointment', 'POST', $this->payload($overrides));
    }

    private function statusRequest(string $token): Request
    {
        return Request::create('/api/v1/appointment-status', 'GET', ['token' => $token]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'phone' => '4165550100',
            'date' => '2026-09-02',
            'time' => '9:30',
            'consultation_type' => 'Buyer',
        ], $overrides);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('re_appointment_bookings');
        Schema::dropIfExists('contacts');

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('subject')->nullable();
            $table->longText('content');
            $table->text('custom_fields')->nullable();
            $table->string('status')->default('unread');
            $table->timestamps();
        });

        Schema::create('re_appointment_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('public_token', 64)->unique();
            $table->string('booking_reference', 32)->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->string('slot_key', 48)->unique();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('consultation_type');
            $table->date('appointment_date');
            $table->string('appointment_time', 16);
            $table->string('timezone', 64)->default('America/Toronto');
            $table->string('source', 128)->nullable();
            $table->string('submitted_page', 255)->nullable();
            $table->string('property_url', 255)->nullable();
            $table->string('assigned_recipient', 255)->nullable();
            $table->string('ghl_contact_id', 64)->nullable();
            $table->string('calendar_event_id', 64)->nullable();
            $table->string('client_mail_id', 128)->nullable();
            $table->string('team_mail_id', 128)->nullable();
            $table->timestamp('client_mail_sent_at')->nullable();
            $table->timestamp('team_mail_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('failed_step', 32)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->json('steps')->nullable();
            $table->timestamps();
        });
    }
}
