<?php

return [

    // these options are related to the sign-up procedure
    'sign_up' => [

        // this option must be set to true if you want to release a token
        // when your user successfully terminates the sign-in procedure
        'release_token' => env('SIGN_UP_RELEASE_TOKEN', false),

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'firstName' => 'required',
            'familyName' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]
    ],
    'change_password' => [
        'validation_rules' => [
            'password' => 'required',
        ]
    ],
    // these options are related to store user
    'store_user' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'firstName' => 'required',
            'familyName' => 'required',
            'email' => 'email',
        ]
    ],
    'notification_subscribe' => [

        'validation_rules' => [
            'userId' => 'required',
            'firebaseToken' => 'required',
        ]
    ],
    // these options are related to store a training
    'store_training' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'start' => 'required',
            'end' => 'required',
        ]
    ],
    'store_group' => [
        'validation_rules' => [
            'name' => 'required',
            'branchId' => 'required',
        ]
    ],
    'store_location' => [
        'validation_rules' => [
            'name' => 'required',
        ]
    ],
    'store_branch' => [
        'validation_rules' => [
            'name' => 'required',
            'shortName' => 'required',
        ]
    ],
    'export_accounting_times' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'userId' => 'required',
            'from' => 'required',
            'to' => 'required',
        ]
    ],
    'store_training_series' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'startTime' => 'required',
            'endTime' => 'required',
            'weekdays' => 'required',
        ]
    ],
    'create_unregistered_user' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'firstName' => 'required',
            'familyName' => 'required',
        ]
    ],

    // these options are related to the login procedure
    'login' => [

        // here you can specify some validation rules for your login request
        'validation_rules' => [
            'email' => 'required|email',
            'password' => 'required'
        ]
    ],

    // these options are related to the password recovery procedure
    'forgot_password' => [

        // here you can specify some validation rules for your password recovery procedure
        'validation_rules' => [
            'email' => 'required|email'
        ]
    ],

    // these options are related to the password recovery procedure
    'reset_password' => [

        // this option must be set to true if you want to release a token
        // when your user successfully terminates the password reset procedure
        'release_token' => env('PASSWORD_RESET_RELEASE_TOKEN', false),

        // here you can specify some validation rules for your password recovery procedure
        'validation_rules' => [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed'
        ]
    ]

];
