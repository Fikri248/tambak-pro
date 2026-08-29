<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role authorization matrix
    |--------------------------------------------------------------------------
    |
    | Keep role capabilities centralized so middleware, requests, and rendered
    | controls share the same two-role access model.
    |
    */
    'abilities' => [
        'dashboard.view' => ['Admin', 'Manager'],
        'locations.view' => ['Admin', 'Manager'],
        'locations.manage' => ['Admin'],
        'commodities.view' => ['Admin', 'Manager'],
        'commodities.manage' => ['Admin'],
        'vendors.view' => ['Admin', 'Manager'],
        'vendors.manage' => ['Admin'],
        'feed-items.view' => ['Admin', 'Manager'],
        'feed-items.manage' => ['Admin'],
        'stocking.view' => ['Admin', 'Manager'],
        'stocking.create' => ['Admin', 'Manager'],
        'stocking.update' => ['Admin', 'Manager'],
        'stocking.delete' => ['Admin', 'Manager'],
        'movements.view' => ['Admin', 'Manager'],
        'movements.create' => ['Admin', 'Manager'],
        'movements.update' => ['Admin', 'Manager'],
        'movements.delete' => ['Admin', 'Manager'],
        'adjustments.view' => ['Admin', 'Manager'],
        'adjustments.create' => ['Admin', 'Manager'],
        'adjustments.update' => ['Admin', 'Manager'],
        'adjustments.delete' => ['Admin', 'Manager'],
        'feeding.view' => ['Admin', 'Manager'],
        'feeding.create' => ['Admin', 'Manager'],
        'feeding.update' => ['Admin', 'Manager'],
        'feeding.delete' => ['Admin', 'Manager'],
        'history.view' => ['Admin', 'Manager'],
        'master-data.view' => ['Admin', 'Manager'],
        'master-data.manage' => ['Admin'],
        'users.manage' => ['Admin'],
        'operations.view' => ['Admin', 'Manager'],
        'operations.manage' => ['Admin', 'Manager'],
        'transactions.view' => ['Admin', 'Manager'],
        'reports.view' => ['Admin', 'Manager'],
    ],
];
