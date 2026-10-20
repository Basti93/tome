<?php

return [

    // Password policy - configurable requirements
    'password_policy' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_digits' => env('PASSWORD_REQUIRE_DIGITS', true),
        'require_special' => env('PASSWORD_REQUIRE_SPECIAL', true),
        'special_chars' => '@$!%*?&.',
    ],

    'change_password' => [
        'validation_rules' => [
            'current_password' => 'required|string',
            'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&.]/',
        ]
    ],
    // these options are related to store user
    'store_user' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'firstName' => 'required',
            'familyName' => 'required',
            'email' => 'email',
            // a path inside storage/app/public as written by ImageController (see App\Support\ProfileImage)
            'profileImageName' => ['nullable', 'string', 'regex:/^profile_images\/[A-Za-z0-9]{20,64}\.(jpg|jpeg|png)$/'],
        ]
    ],
    'store_user_absence' => [

        'validation_rules' => [
            // absenceStart/absenceEnd are ISO 8601 strings (the controller parses them); further
            // date-range rules live in StoreUserAbsenceRequest.
            'absenceStart' => 'required',
            'absenceEnd' => 'required',
            'absenceReason' => 'nullable|string|max:255',
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
            'userId' => 'required|integer',
            'from' => 'required',
            'to' => 'required',
        ]
    ],
    'store_training_series' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'startTime' => 'required',
            'endTime' => 'required',
            'weekdays' => 'required|array|list|min:1',
            'weekdays.*' => 'integer:strict|between:1,7',
        ]
    ],
    'create_unregistered_user' => [

        // here you can specify some validation rules for your sign-in request
        'validation_rules' => [
            'firstName' => 'required',
            'familyName' => 'required',
            'profileImageName' => ['nullable', 'string', 'regex:/^profile_images\/[A-Za-z0-9]{20,64}\.(jpg|jpeg|png)$/'],
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
            'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&.]/|confirmed'
        ]
    ]

];
