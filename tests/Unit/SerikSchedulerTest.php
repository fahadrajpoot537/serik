<?php

namespace Tests\Unit;

use App\Support\SerikScheduler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SerikSchedulerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('serik.scheduler.max_low_queue_depth', 3);
        Schema::dropIfExists('jobs');
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function test_should_dispatch_heavy_low_when_queue_is_light(): void
    {
        $this->assertTrue(SerikScheduler::shouldDispatchHeavyLow());
    }

    public function test_should_not_dispatch_heavy_low_when_queue_is_busy(): void
    {
        for ($i = 0; $i < 3; $i++) {
            DB::table('jobs')->insert([
                'queue' => 'low',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]);
        }

        $this->assertFalse(SerikScheduler::shouldDispatchHeavyLow());
    }
}
