<?php

use Botble\RealEstate\Models\Property;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    */
    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    | When false, index writes happen inline (local Meilisearch is sub-ms).
    | Turn on together with a Redis/database queue worker for very large bulk
    | re-imports so listing saves never wait on the search engine.
    */
    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    */
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),

        'index-settings' => [
            Property::class => [
                // Order matters: earlier attributes rank higher on a match.
                'searchableAttributes' => [
                    'external_id',      // MLS number / ListingKey
                    'name',             // full unparsed address
                    'street',
                    'street_number',
                    'street_name',
                    'location',
                    'community',
                    'city',
                    'postal_code',
                    'unit',
                    'broker',
                    'property_sub_type',
                    'keywords',
                ],

                'filterableAttributes' => [
                    'city',
                    'community',
                    'street_number',
                    'street_name',
                    'unit',
                    'property_sub_type',
                    'transaction_type',
                    'mls_status',
                    'status',
                    'is_sold',
                    'number_bedroom',
                    'number_bathroom',
                    'covered_spaces',
                    'parking_spaces',
                    'price',
                    'close_price',
                    'square',
                    'zip_code',
                    'listing_year',
                    'listing_contract_ts',
                    'close_ts',
                    'created_ts',
                    'updated_ts',
                    '_geo',
                ],

                'sortableAttributes' => [
                    'price',
                    'close_price',
                    'square',
                    'number_bedroom',
                    'number_bathroom',
                    'listing_contract_ts',
                    'close_ts',
                    'created_ts',
                    'updated_ts',
                    '_geo',
                ],

                // words -> typo -> proximity -> attribute -> sort -> exactness,
                // then freshest listings first as the final tie-breaker.
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'updated_ts:desc',
                ],

                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 4,
                        'twoTypos' => 8,
                    ],
                    // MLS numbers & postal codes are exact identifiers.
                    'disableOnAttributes' => [
                        'external_id',
                        'postal_code',
                    ],
                ],

                'faceting' => [
                    'maxValuesPerFacet' => 200,
                ],

                'pagination' => [
                    'maxTotalHits' => 20000,
                ],
            ],
        ],
    ],

];
