<?php

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,
        'price_krw' => 0,
        'stripe_price_id' => null,
        'trial_days' => 0,
        'features' => [
            'basic_dashboard',
            'community_support',
        ],
        'limits' => [
            'api_calls_per_day' => 100,
            'storage_mb' => 100,
            'members' => 3,
        ],
        'description' => '시작하기에 적합',
    ],

    'pro' => [
        'name' => 'Pro',
        'price' => 29,
        'price_krw' => 33000,
        'stripe_price_id' => env('STRIPE_PRICE_PRO'),
        'trial_days' => 14,
        'features' => [
            'basic_dashboard',
            'community_support',
            'advanced_analytics',
            'priority_support',
            'custom_domain',
            'api_access',
        ],
        'limits' => [
            'api_calls_per_day' => 10000,
            'storage_mb' => 10240,
            'members' => 20,
        ],
        'description' => '성장하는 팀을 위해',
    ],

    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 99,
        'price_krw' => 110000,
        'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
        'trial_days' => 14,
        'features' => [
            'basic_dashboard',
            'community_support',
            'advanced_analytics',
            'priority_support',
            'custom_domain',
            'api_access',
            'sso',
            'audit_log',
            'dedicated_support',
        ],
        'limits' => [
            'api_calls_per_day' => PHP_INT_MAX,
            'storage_mb' => PHP_INT_MAX,
            'members' => PHP_INT_MAX,
        ],
        'description' => '대규모 조직을 위해',
    ],
];
