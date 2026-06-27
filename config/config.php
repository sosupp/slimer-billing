<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    */

    // Billing number format
    'billing_number_prefix' => 'INV',
    
    // Default status for new billings
    'default_status' => 'draft',

    // Available statuses
    'statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'sent' => 'Sent',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

    // Available types
    'types' => [
        'invoice' => 'Invoice',
        'receipt' => 'Receipt',
        'credit_note' => 'Credit Note',
        'debit_note' => 'Debit Note',
    ],

    // Currency settings
    'currency' => [
        'default' => 'GHS',
        'symbols' => [
            'GHS' => '¢',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'NGN' => '₦',
        ],
    ],

    // Tax settings
    'tax' => [
        'default_rate' => 0,
        'rates' => [
            'standard' => 0,
            'reduced' => 0,
            'zero' => 0,
        ],
    ],

    // PDF settings
    'pdf' => [
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'font' => 'Arial',
        'logo_path' => null,
        'show_barcode' => true,
    ],

    // Routes configuration
    'routes' => [
        'prefix' => 'billing',
        'middleware' => ['web', 'auth'],
        'api_middleware' => ['api', 'auth:api'],
    ],

    // Model configurations
    'models' => [
        'billing' => \Sosupp\SlimerBilling\Models\Billing::class,
        'billing_item' => \Sosupp\SlimerBilling\Models\BillingItem::class,
        'billing_payment' => \Sosupp\SlimerBilling\Models\BillingPayment::class,
        'billing_line_item' => \Sosupp\SlimerBilling\Models\BillingLineItem::class,
    ],

    // Enable features
    'features' => [
        'auto_generate_billing_number' => true,
        'allow_draft_editing' => true,
        'allow_status_update' => true,
        'enable_duplicate' => true,
        'enable_payments' => true,
        'enable_notifications' => true,
        'enable_approval_workflow' => false,
        'enable_multi_currency' => true,
    ],

    // Notification settings
    'notifications' => [
        'on_create' => false,
        'on_update' => false,
        'on_status_change' => false,
        'on_payment' => true,
        'on_overdue' => true,
    ],
];