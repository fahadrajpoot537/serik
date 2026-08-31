<?php

namespace Tests\Unit;

use App\Support\AppointmentScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppointmentSchedulerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'serik.appointment.timezone' => 'America/Toronto',
            'serik.appointment.max_year' => 2030,
        ]);
        Cache::flush();
    }

    public function test_today_is_identified_in_the_configured_business_timezone(): void
    {
        $now = Carbon::parse('2026-08-31 23:30:00', 'UTC');
        $this->assertSame('America/Toronto', AppointmentScheduler::timezoneId());
        $this->assertSame('2026-08-31', AppointmentScheduler::todayDateString($now));

        $late = Carbon::parse('2026-09-01 03:30:00', 'UTC');
        $this->assertSame('2026-08-31', AppointmentScheduler::todayDateString($late));

        $cfg = AppointmentScheduler::frontendConfig($now);
        $this->assertSame('2026-08-31', $cfg['today']);
        $this->assertSame('America/Toronto', $cfg['timezone']);
    }

    public function test_today_is_selectable_when_a_future_slot_is_available(): void
    {
        $now = Carbon::parse('2026-08-31 10:00:00', 'America/Toronto');
        $this->assertTrue(AppointmentScheduler::dateHasAvailableSlot('2026-08-31', $now, []));
        $this->assertTrue(AppointmentScheduler::isSlotAvailable('2026-08-31', '10:30', $now, []));
        $this->assertTrue(AppointmentScheduler::frontendConfig($now)['todayHasSlots']);
    }

    public function test_today_is_disabled_when_no_future_slots_remain(): void
    {
        $now = Carbon::parse('2026-08-31 19:00:00', 'America/Toronto');
        $this->assertFalse(AppointmentScheduler::dateHasAvailableSlot('2026-08-31', $now, []));
        $this->assertFalse(AppointmentScheduler::frontendConfig($now)['todayHasSlots']);
        $this->assertFalse(AppointmentScheduler::isSlotAvailable('2026-08-31', '6:30', $now, []));
    }

    public function test_past_dates_and_blackout_window_cannot_be_submitted(): void
    {
        $now = Carbon::parse('2026-08-31 10:00:00', 'America/Toronto');
        $past = AppointmentScheduler::validateBooking($this->payload(['date' => '2026-08-30']), $now, []);
        $this->assertFalse($past['ok']);
        $this->assertArrayHasKey('date', $past['errors']);

        $beyond = AppointmentScheduler::validateBooking($this->payload(['date' => '2031-01-01']), $now, []);
        $this->assertFalse($beyond['ok']);

        $invalid = AppointmentScheduler::validateBooking($this->payload(['date' => '2026-02-31']), $now, []);
        $this->assertFalse($invalid['ok']);
    }

    public function test_future_available_dates_can_be_selected(): void
    {
        $now = Carbon::parse('2026-08-31 10:00:00', 'America/Toronto');
        $ok = AppointmentScheduler::validateBooking($this->payload(['date' => '2026-09-02', 'time' => '9:30']), $now, []);
        $this->assertTrue($ok['ok']);
        $this->assertSame('2026-09-02', $ok['date']);
        $this->assertSame('9:30', $ok['time']);
    }

    public function test_past_time_slots_on_today_are_unavailable(): void
    {
        $now = Carbon::parse('2026-08-31 11:00:00', 'America/Toronto');
        $this->assertFalse(AppointmentScheduler::isSlotAvailable('2026-08-31', '9:30', $now, []));
        $this->assertFalse(AppointmentScheduler::isSlotAvailable('2026-08-31', '10:30', $now, []));
        $this->assertTrue(AppointmentScheduler::isSlotAvailable('2026-08-31', '11:30', $now, []));

        $rejected = AppointmentScheduler::validateBooking($this->payload(['date' => '2026-08-31', 'time' => '9:30']), $now, []);
        $this->assertFalse($rejected['ok']);
        $this->assertArrayHasKey('time', $rejected['errors']);
    }

    public function test_displayed_times_include_am_pm_and_preserve_canonical_values(): void
    {
        $labels = array_column(AppointmentScheduler::catalog(), 'label');
        $values = AppointmentScheduler::canonicalTimes();

        foreach ($labels as $label) {
            $this->assertMatchesRegularExpression('/^\d{1,2}:\d{2} (AM|PM)$/', $label);
        }

        $this->assertSame('9:30 AM', AppointmentScheduler::formatDisplay(9, 30));
        $this->assertSame('11:30 AM', AppointmentScheduler::formatDisplay(11, 30));
        $this->assertSame('12:00 PM', AppointmentScheduler::formatDisplay(12, 0));
        $this->assertSame('12:00 AM', AppointmentScheduler::formatDisplay(0, 0));
        $this->assertSame('1:30 PM', AppointmentScheduler::formatDisplay(13, 30));
        $this->assertSame('5:30 PM', AppointmentScheduler::formatDisplay(17, 30));
        $this->assertContains('9:30', $values);
        $this->assertContains('1:30', $values);
        $this->assertSame('1:30', AppointmentScheduler::slotByCanonical('1:30')['value']);
        $this->assertSame(13, AppointmentScheduler::slotByCanonical('1:30')['hour']);
    }

    public function test_property_address_is_absent_from_consultation_form_but_present_on_property_forms(): void
    {
        $appointment = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/property-categories/styles/style-2.blade.php'));
        $this->assertStringNotContainsString('Property Address', $appointment);
        $this->assertStringNotContainsString('name="address"', $appointment);
        $this->assertStringContainsString('Consultation Type', $appointment);
        $this->assertStringContainsString('setAttribute("aria-current", "date")', $appointment);
        $this->assertStringContainsString('is-today', $appointment);

        $estimate = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/contact-form/styles/style-2.blade.php'));
        $this->assertStringContainsString('Please enter your property address', $estimate);

        $showing = file_get_contents(base_path('platform/themes/homzen/views/real-estate/single-layouts/partials/contact.blade.php'));
        $this->assertStringContainsString('Schedule Viewing', $showing);
        $this->assertStringContainsString('ConsultForm::create()', $showing);
    }

    public function test_consultation_type_is_required_and_only_approved_values_are_accepted(): void
    {
        $now = Carbon::parse('2026-08-31 10:00:00', 'America/Toronto');
        $missing = AppointmentScheduler::validateBooking($this->payload(['consultation_type' => '']), $now, []);
        $this->assertFalse($missing['ok']);
        $this->assertArrayHasKey('consultation_type', $missing['errors']);

        $bad = AppointmentScheduler::validateBooking($this->payload(['consultation_type' => 'Investor']), $now, []);
        $this->assertFalse($bad['ok']);

        foreach (AppointmentScheduler::CONSULTATION_TYPES as $type) {
            $ok = AppointmentScheduler::validateBooking($this->payload([
                'date' => '2026-09-02',
                'consultation_type' => $type,
            ]), $now, []);
            $this->assertTrue($ok['ok'], $type . ' should be accepted');
            $this->assertSame($type, $ok['consultation_type']);
        }

        $this->assertSame([
            'Buyer',
            'Seller',
            'Tenant',
            'Landlord',
            'General Consultation',
        ], AppointmentScheduler::CONSULTATION_TYPES);

        $blade = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/property-categories/styles/style-2.blade.php'));
        $this->assertStringContainsString('AppointmentScheduler::CONSULTATION_TYPES', $blade);
        $this->assertStringContainsString('name="consultation_type"', $blade);
    }

    public function test_consultation_type_is_stored_in_appointment_remarks_and_custom_fields(): void
    {
        $remarks = AppointmentScheduler::remarks('2026-09-02', '1:30', 'Tenant');
        $this->assertStringContainsString('Appointment Date: 2026-09-02 Time: 1:30', $remarks);
        $this->assertStringContainsString('Consultation Type: Tenant', $remarks);
        $this->assertStringContainsString('1:30 PM', $remarks);
    }

    public function test_stale_or_already_booked_slot_is_rejected(): void
    {
        $now = Carbon::parse('2026-08-31 10:00:00', 'America/Toronto');
        $stale = AppointmentScheduler::validateBooking(
            $this->payload(['date' => '2026-09-02', 'time' => '9:30']),
            $now,
            ['9:30']
        );
        $this->assertFalse($stale['ok']);
        $this->assertNotEmpty($stale['stale'] ?? null);
        $this->assertArrayHasKey('time', $stale['errors']);
    }

    public function test_slot_lock_prevents_two_reservations_of_the_same_time(): void
    {
        $key = AppointmentScheduler::slotLockKey('2026-09-02', '9:30');
        $this->assertTrue(Cache::lock($key, 10)->get());
        $this->assertFalse(Cache::lock($key, 10)->get());
    }

    public function test_existing_success_message_and_mobile_form_contract_remain(): void
    {
        $blade = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/property-categories/styles/style-2.blade.php'));
        $this->assertStringContainsString('Appointment booked successfully', $blade);
        $this->assertStringContainsString('appointmentForm', $blade);
        $this->assertStringContainsString('consultation_type', $blade);
        $this->assertStringContainsString('SERIK_APPOINTMENT', $blade);
        $this->assertStringContainsString('All Times are in Eastern Time - US & Canada', $blade);
        $this->assertStringContainsString('grid-template-columns: repeat(3, 1fr)', $blade);
        $this->assertStringContainsString('AbortController', $blade);
        $this->assertStringContainsString('aria-busy', $blade);
        $this->assertStringContainsString('zoom:0.7', $blade);
        $this->assertStringContainsString('confirming your appointment', $blade);
        $this->assertStringContainsString('appointment_booked', $blade);
    }

    public function test_empty_booking_request_keeps_json_error_contract(): void
    {
        $response = AppointmentScheduler::book(Request::create('/api/v1/book-appointment', 'POST', []));
        $this->assertFalse($response->getData(true)['status']);
        $this->assertContains($response->getStatusCode(), [422, 429]);
        $this->assertArrayHasKey('message', $response->getData(true));
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
}
