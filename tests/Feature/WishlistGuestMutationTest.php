<?php

namespace Tests\Feature;

use Tests\TestCase;

class WishlistGuestMutationTest extends TestCase
{
    public function test_guest_cannot_toggle_wishlist(): void
    {
        $response = $this->postJson('/ajax/wishlist/toggle', [
            'id' => 1,
            'type' => 'property',
            'action' => 'add',
        ]);

        $this->assertTrue(
            in_array($response->status(), [200, 401, 403, 404, 419, 422, 500, 503], true),
            (string) $response->status() . ' ' . $response->getContent()
        );
        if ($response->status() === 401) {
            $response->assertJson(['login_required' => true]);
        }
        if ($response->status() === 200) {
            $this->assertNotFalse($response->json('error'));
        }
        $this->assertStringNotContainsString('"saved":true', (string) $response->getContent());
    }

    public function test_guest_state_endpoint_does_not_leak_ids(): void
    {
        $response = $this->getJson('/ajax/wishlist/state');

        $this->assertNotSame(200, $response->status(), (string) $response->getContent());
        if ($response->status() === 401) {
            $response->assertJson([
                'authenticated' => false,
                'ids' => [],
                'count' => 0,
            ]);
        }
        $this->assertStringNotContainsString('"authenticated":true', (string) $response->getContent());
    }
}
