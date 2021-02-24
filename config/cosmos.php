<?php

return [
    'account_name' => env('COSMOS_ACCOUNT_NAME'),
    'access_token' => env('COSMOS_ACCESS_TOKEN'),
    'defaults'     => [
        'database' => [
            'name'       => env('COSMOS_DB_NAME'),
            'collection' => env('COSMOS_DB_COLLECTION_NAME'),
        ],
    ],

];
