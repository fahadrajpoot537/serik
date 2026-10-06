<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Geoapify (server-side geocoding only — never expose to the browser)
    |--------------------------------------------------------------------------
    */
    'geoapify' => [
        'api_key' => env('GEOAPIFY_API_KEY'),
        'geocode_url' => env('GEOAPIFY_GEOCODE_URL', 'https://api.geoapify.com/v1/geocode/search'),
        'timeout' => (int) env('GEOAPIFY_TIMEOUT', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistics Canada — 2021 Dissemination Area + Census Profile (SDMX)
    |--------------------------------------------------------------------------
    */
    'statcan' => [
        'da_identify_url' => env(
            'STATCAN_DA_IDENTIFY_URL',
            'https://geo.statcan.gc.ca/geo_wa/rest/services/2021/Digital_boundary_files/MapServer/identify'
        ),
        'da_layer' => env('STATCAN_DA_LAYER', 'all:12'),
        'profile_url' => env(
            'STATCAN_CENSUS_PROFILE_URL',
            'https://api.statcan.gc.ca/census-recensement/profile/sdmx/rest/data/STC_CP,DF_DA,1.3'
        ),
        'profile_page_url' => env(
            'STATCAN_PROFILE_PAGE_URL',
            'https://www12.statcan.gc.ca/census-recensement/2021/dp-pd/prof/details/page.cfm'
        ),
        'timeout' => (int) env('STATCAN_HTTP_TIMEOUT', 60),
        'geo_ssl_verify' => filter_var(
            env('CENSUS_GEO_SSL_VERIFY', env('APP_ENV') === 'local' ? 'false' : 'true'),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'cache_ttl' => (int) env('CENSUS_CACHE_TTL', 2592000),

    /*
    |--------------------------------------------------------------------------
    | Summary metric characteristic IDs
    |--------------------------------------------------------------------------
    */
    'characteristics' => [
        'population_2021' => '1',
        'average_age' => '39',
        'median_age' => '40',
        'average_household_size' => '56',
        'average_household_income' => '238',
        'median_household_income' => '229',
        'tenure_total' => '1400',
        'owner' => '1401',
        'renter' => '1402',
        'condo_total' => '1404',
        'condominium' => '1405',
        'immigrant_status_total' => '1513',
        'immigrants' => '1515',
        'education_25_64_total' => '2014',
        'college_cegep' => '2022',
        'university_below_bachelor' => '2023',
        'bachelors_or_higher' => '2024',
        'average_dwelling_value' => '1475',
        'median_dwelling_value' => '1474',
        'lim_at_prevalence' => '331',
        'lim_at_in_low_income' => '326',
        'lim_at_total' => '321',
        'labour_force_total_15' => '2223',
        'not_in_labour_force' => '2227',
        'marital_status_total_15' => '58',
        'never_married_not_common_law' => '67',
        'household_type_total' => '100',
        'couple_with_children' => '103',
        'one_parent_family_households' => '105',
        'religion_total' => '1935',
        'income_group_total' => '246',
        'age_total' => '8',
        'visible_minority_total' => '1670',
        'mother_tongue_total' => '377',
        'construction_total' => '1426',
        'occupation_total' => '2248',
        'structural_type_total' => '41',
        'commute_total' => '2603',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pie-chart category characteristic IDs (aggregated where noted)
    |--------------------------------------------------------------------------
    */
    'charts' => [
        'household_income' => [
            'label' => 'Household Income',
            'mode' => 'aggregate',
            'universe_id' => '246',
            'slices' => [
                ['label' => '$0 - $29,999', 'ids' => ['247', '248', '249', '250', '251', '252']],
                ['label' => '$30,000 - $59,999', 'ids' => ['253', '254', '255', '256', '257']],
                ['label' => '$60,000 - $79,999', 'ids' => ['258', '259']],
                ['label' => '$80,000 - $99,999', 'ids' => ['260', '261']],
                ['label' => '$100,000 - $149,999', 'ids' => ['263', '264']],
                ['label' => '$150,000 - $199,999', 'ids' => ['265']],
                ['label' => '$200,000+', 'ids' => ['266']],
            ],
        ],
        'age' => [
            'label' => 'Age',
            'mode' => 'items',
            'universe_id' => '8',
            'items' => [
                ['label' => '0 to 14', 'id' => '9'],
                ['label' => '15 to 24', 'ids' => ['14', '15']],
                ['label' => '25 to 34', 'ids' => ['16', '17']],
                ['label' => '35 to 44', 'ids' => ['18', '19']],
                ['label' => '45 to 54', 'ids' => ['20', '21']],
                ['label' => '55 to 64', 'ids' => ['22', '23']],
                ['label' => '65+', 'id' => '24'],
            ],
        ],
        'education' => [
            'label' => 'Education',
            'mode' => 'items',
            'universe_id' => '2014',
            'items' => [
                ['label' => 'No certificate', 'id' => '2015'],
                ['label' => 'High school', 'id' => '2016'],
                ['label' => 'Apprenticeship / trades', 'id' => '2019'],
                ['label' => 'College / CEGEP', 'id' => '2022'],
                ['label' => 'University below bachelor', 'id' => '2023'],
                ['label' => "Bachelor's or higher", 'id' => '2024'],
            ],
        ],
        'ethnicity' => [
            'label' => 'Ethnicity (Top 10)',
            'mode' => 'top_n',
            'top' => 10,
            'universe_id' => '1670',
            'items' => [
                ['label' => 'South Asian', 'id' => '1671'],
                ['label' => 'Chinese', 'id' => '1672'],
                ['label' => 'Black', 'id' => '1673'],
                ['label' => 'Filipino', 'id' => '1674'],
                ['label' => 'Arab', 'id' => '1675'],
                ['label' => 'Latin American', 'id' => '1676'],
                ['label' => 'Southeast Asian', 'id' => '1677'],
                ['label' => 'West Asian', 'id' => '1678'],
                ['label' => 'Korean', 'id' => '1679'],
                ['label' => 'Japanese', 'id' => '1680'],
                ['label' => 'Visible minority, n.i.e.', 'id' => '1681'],
                ['label' => 'Multiple visible minorities', 'id' => '1682'],
                ['label' => 'Not a visible minority', 'id' => '1683'],
            ],
        ],
        'language' => [
            'label' => 'Language (Top 10)',
            'mode' => 'top_n',
            'top' => 10,
            'universe_id' => '377',
            'items' => [
                ['label' => 'English', 'id' => '382'],
                ['label' => 'French', 'id' => '383'],
                ['label' => 'Mandarin', 'id' => '672'],
                ['label' => 'Cantonese', 'id' => '676'],
                ['label' => 'Punjabi', 'id' => '601'],
                ['label' => 'Spanish', 'id' => '629'],
                ['label' => 'Tagalog', 'id' => '523'],
                ['label' => 'Arabic', 'id' => '492'],
                ['label' => 'Italian', 'id' => '626'],
                ['label' => 'German', 'id' => '575'],
                ['label' => 'Portuguese', 'id' => '628'],
                ['label' => 'Urdu', 'id' => '607'],
                ['label' => 'Persian', 'id' => '620'],
                ['label' => 'Korean', 'id' => '633'],
                ['label' => 'Russian', 'id' => '556'],
                ['label' => 'Polish', 'id' => '555'],
                ['label' => 'Vietnamese', 'id' => '505'],
                ['label' => 'Turkish', 'id' => '695'],
                ['label' => 'Hindi', 'id' => '598'],
                ['label' => 'Gujarati', 'id' => '597'],
            ],
        ],
        'religion' => [
            'label' => 'Religion',
            'mode' => 'items',
            'universe_id' => '1935',
            'items' => [
                ['label' => 'Christian', 'id' => '1937'],
                ['label' => 'Muslim', 'id' => '1955'],
                ['label' => 'Hindu', 'id' => '1953'],
                ['label' => 'Jewish', 'id' => '1954'],
                ['label' => 'Sikh', 'id' => '1956'],
                ['label' => 'Buddhist', 'id' => '1936'],
                ['label' => 'Traditional spirituality', 'id' => '1957'],
                ['label' => 'Other religions', 'id' => '1958'],
                ['label' => 'No religion', 'id' => '1959'],
            ],
        ],
        'construction' => [
            'label' => 'Construction',
            'mode' => 'items',
            'universe_id' => '1426',
            'items' => [
                ['label' => '1960 or before', 'id' => '1427'],
                ['label' => '1961 to 1980', 'id' => '1428'],
                ['label' => '1981 to 1990', 'id' => '1429'],
                ['label' => '1991 to 2000', 'id' => '1430'],
                ['label' => '2001 to 2005', 'id' => '1431'],
                ['label' => '2006 to 2010', 'id' => '1432'],
                ['label' => '2011 to 2015', 'id' => '1433'],
                ['label' => '2016 to 2021', 'id' => '1434'],
            ],
        ],
        'occupation' => [
            'label' => 'Occupation',
            'mode' => 'items',
            'universe_id' => '2248',
            'items' => [
                ['label' => 'Business / finance / admin', 'id' => '2250'],
                ['label' => 'Natural & applied sciences', 'id' => '2251'],
                ['label' => 'Health', 'id' => '2252'],
                ['label' => 'Education / law / government', 'id' => '2253'],
                ['label' => 'Art / culture / recreation', 'id' => '2254'],
                ['label' => 'Sales and service', 'id' => '2255'],
                ['label' => 'Trades / transport', 'id' => '2256'],
                ['label' => 'Natural resources', 'id' => '2257'],
                ['label' => 'Manufacturing / utilities', 'id' => '2258'],
                ['label' => 'Legislative / senior management', 'id' => '2249'],
            ],
        ],
        'housing' => [
            'label' => 'Housing',
            'mode' => 'items',
            'universe_id' => '41',
            'items' => [
                ['label' => 'Single-detached', 'id' => '42'],
                ['label' => 'Semi-detached', 'id' => '43'],
                ['label' => 'Row house', 'id' => '44'],
                ['label' => 'Duplex apartment', 'id' => '45'],
                ['label' => 'Apartment < 5 storeys', 'id' => '46'],
                ['label' => 'Apartment 5+ storeys', 'id' => '47'],
                ['label' => 'Other single-attached', 'id' => '48'],
                ['label' => 'Movable dwelling', 'id' => '49'],
            ],
        ],
        'commute' => [
            'label' => 'Commute Method',
            'mode' => 'items',
            'universe_id' => '2603',
            'items' => [
                ['label' => 'Car / truck / van', 'id' => '2604'],
                ['label' => 'Public transit', 'id' => '2607'],
                ['label' => 'Walked', 'id' => '2608'],
                ['label' => 'Bicycle', 'id' => '2609'],
                ['label' => 'Other method', 'id' => '2610'],
            ],
        ],
    ],

    'chart_colors' => [
        '#2563eb',
        '#06b6d4',
        '#10b981',
        '#eab308',
        '#f97316',
        '#ec4899',
        '#a855f7',
        '#14b8a6',
        '#ef4444',
        '#84cc16',
    ],

];
