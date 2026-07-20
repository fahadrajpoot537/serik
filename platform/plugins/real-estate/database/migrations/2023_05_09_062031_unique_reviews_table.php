<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $hasUniqueConstraint = false;

        try {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('re_reviews');
            $hasUniqueConstraint = isset($indexes['reviews_unique']);
        } catch (Exception $e) {
        }

        if ($hasUniqueConstraint) {
            return;
        }

        Schema::create('re_reviews_tmp', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id');
            $table->morphs('reviewable');
            $table->tinyInteger('star');
            $table->string('content', 500);
            $table->string('status', 60)->default('approved');
            $table->timestamps();
        });

        DB::table('re_reviews')->oldest('id')->chunk(100, function ($reviews): void {
            $data = [];
            foreach ($reviews as $review) {
                $data[] = [
                    'id' => $review->id,
                    'account_id' => $review->account_id,
                    'reviewable_id' => $review->reviewable_id,
                    'reviewable_type' => $review->reviewable_type,
                    'star' => $review->star,
                    'content' => $review->content,
                    'status' => $review->status ?? 'approved',
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            }
            if (! empty($data)) {
                DB::table('re_reviews_tmp')->insert($data);
            }
        });

        DB::table('re_reviews')->delete();

        Schema::table('re_reviews', function (Blueprint $table): void {
            $table->unique(['account_id', 'reviewable_id', 'reviewable_type'], 'reviews_unique');
        });

        DB::table('re_reviews_tmp')->oldest('id')->chunk(100, function ($reviews): void {
            foreach ($reviews as $review) {
                try {
                    DB::table('re_reviews')->insert([
                        'id' => $review->id,
                        'account_id' => $review->account_id,
                        'reviewable_id' => $review->reviewable_id,
                        'reviewable_type' => $review->reviewable_type,
                        'star' => $review->star,
                        'content' => $review->content,
                        'status' => $review->status,
                        'created_at' => $review->created_at,
                        'updated_at' => $review->updated_at,
                    ]);
                } catch (Exception $e) {
                }
            }
        });

        Schema::dropIfExists('re_reviews_tmp');
    }

    public function down(): void
    {
        Schema::table('re_reviews', function (Blueprint $table): void {
            $table->dropUnique('reviews_unique');
        });
    }
};
