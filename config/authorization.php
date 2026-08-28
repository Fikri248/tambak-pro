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
        'stocking.create' => ['Admin'],
        'stocking.update' => ['Admin'],
        'stocking.delete' => ['Admin'],
        'movements.view' => ['Admin', 'Manager'],
        'movements.create' => ['Admin'],
        'movements.update' => ['Admin'],
        'movements.delete' => ['Admin'],
        'adjustments.view' => ['Admin', 'Manager'],
        'adjustments.create' => ['Admin'],
        'adjustments.update' => ['Admin'],
        'adjustments.delete' => ['Admin'],
        'feeding.view' => ['Admin', 'Manager'],
        'feeding.create' => ['Admin'],
        'feeding.update' => ['Admin'],
        'feeding.delete' => ['Admin'],
        'history.view' => ['Admin', 'Manager'],
        'master-data.view' => ['Admin'],
        'master-data.manage' => ['Admin'],
        'users.manage' => ['Admin'],
        'operations.view' => ['Admin', 'Manager'],
        'operations.manage' => ['Admin'],
        'transactions.view' => ['Admin', 'Manager'],
        'reports.view' => ['Admin', 'Manager'],
    ],
];
