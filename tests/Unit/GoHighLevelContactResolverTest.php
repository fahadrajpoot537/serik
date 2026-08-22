<?php

namespace Tests\Unit;

use App\Services\GoHighLevel\GoHighLevelContactResolver;
use Tests\TestCase;

class GoHighLevelContactResolverTest extends TestCase
{
    public function test_rejects_full_name_as_contact_id_shape(): void
    {
        $resolver = app(GoHighLevelContactResolver::class);

        $this->assertFalse($resolver->looksLikeGhlContactId('Jane Example'));
        $this->assertFalse($resolver->looksLikeGhlContactId('jane@example.com'));
        $this->assertTrue($resolver->looksLikeGhlContactId('i2eROA1l8CZcwH5pzWQe'));
    }
}
