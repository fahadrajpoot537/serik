<?php

namespace Tests\Unit;

use App\Support\SerikAccountAuth;
use App\Support\SerikQueueJobHygiene;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage1StabilityFixesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('jobs');
        Schema::create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('jobs');
        parent::tearDown();
    }

    public function test_account_guard_is_defined_in_auth_config(): void
    {
        $this->assertArrayHasKey('account', config('auth.guards'));
        $this->assertArrayHasKey('accounts', config('auth.providers'));
    }

    public function test_serik_account_auth_never_throws_when_guard_missing(): void
    {
        Config::set('auth.guards', ['web' => config('auth.guards.web')]);

        $this->assertFalse(SerikAccountAuth::check());
        $this->assertNull(SerikAccountAuth::id());
    }

    public function test_prune_duplicates_keeps_newest_sync_live_job(): void
    {
        $payload = json_encode(['displayName' => 'App\\Jobs\\SyncLiveJob']);

        foreach ([101, 102, 103] as $id) {
            DB::table('jobs')->insert([
                'id' => $id,
                'queue' => 'high',
                'payload' => $payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => time(),
                'created_at' => time(),
            ]);
        }

        $result = SerikQueueJobHygiene::pruneDuplicates(
            'App\\Jobs\\SyncLiveJob',
            'high',
            1,
            false
        );

        $this->assertSame(3, $result['matched']);
        $this->assertSame(2, $result['deleted']);
        $this->assertSame([103], $result['kept_ids']);
        $this->assertSame(1, DB::table('jobs')->count());
    }
}
