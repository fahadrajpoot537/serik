<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pending / completed MLS → GHL Showings sync task.
 *
 * @property int $id
 * @property string $contact_id
 * @property string $mls_number
 * @property string|null $showing_record_id
 * @property string|null $location_id
 * @property string $status
 * @property string|null $external_key
 * @property int $attempts
 * @property string|null $last_error
 * @property string|null $sync_hash
 * @property array<string, mixed>|null $source_payload
 * @property array<string, mixed>|null $mapped_fields
 */
class GhlMlsSyncTask extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'ghl_mls_sync_tasks';

    protected $fillable = [
        'contact_id',
        'mls_number',
        'showing_record_id',
        'location_id',
        'status',
        'external_key',
        'attempts',
        'last_error',
        'sync_hash',
        'source_payload',
        'mapped_fields',
        'queued_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'source_payload' => 'array',
        'mapped_fields' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRetryable(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    public static function makeExternalKey(string $contactId, string $mlsNumber, ?string $showingRecordId = null): string
    {
        $showing = strtolower(trim((string) $showingRecordId));
        if ($showing !== '') {
            return 'showing:' . $showing . ':' . strtoupper(trim($mlsNumber));
        }

        return strtolower(trim($contactId)) . ':' . strtoupper(trim($mlsNumber));
    }
}
