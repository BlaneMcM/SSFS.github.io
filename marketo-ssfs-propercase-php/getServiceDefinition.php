<?php
require __DIR__ . '/common.php';
requireApiKey();

// NOTE: This endpoint mirrors the same shape registered in openapi.yaml
// (under components.schemas.serviceDefinition), mainly so it's easy to
// sanity-check by visiting the URL directly in a browser.

sendJson(200, [
    'apiName' => 'properCaseName',
    'i18n' => [
        'en_US' => [
            'name' => 'Proper Case Name',
            'description' => "Converts First Name and/or Last Name fields to proper case (e.g. MCMICHEN -> McMichen, o'rielly -> O'Rielly) for cleaner data and better email personalization. Map either field, or both -- whichever are mapped get processed on every run.",
        ],
    ],
    'primaryAttribute' => 'batchLabel',
    'invocationPayloadDef' => [
        'flowAttributes' => [
            [
                // Purely a descriptive, optional label logged to the
                // activity for context (e.g. "Newsletter Signup
                // Cleanup") -- it doesn't affect which fields get
                // processed. Marketo requires a flow attribute to
                // serve as primaryAttribute, so this one does double
                // duty as something actually useful to see in reports.
                'apiName' => 'batchLabel',
                'i18n' => ['en_US' => ['name' => 'Batch Label (optional)']],
                'dataType' => 'string',
            ],
        ],
        'fields' => [
            [
                'required' => false,
                'serviceAttribute' => 'FirstNameValue',
                'suggestedMarketoAttribute' => 'firstName',
                'description' => ['en_US' => 'First Name value to convert to proper case. Leave unmapped to skip First Name processing.'],
                'dataType' => 'string',
            ],
            [
                'required' => false,
                'serviceAttribute' => 'LastNameValue',
                'suggestedMarketoAttribute' => 'lastName',
                'description' => ['en_US' => 'Last Name value to convert to proper case. Leave unmapped to skip Last Name processing.'],
                'dataType' => 'string',
            ],
        ],
        'userDrivenMapping' => false,
        'programContext' => false,
        'campaignContext' => false,
        'triggerContext' => false,
        'programMemberContext' => false,
        'subscriptionContext' => false,
    ],
    'callbackPayloadDef' => [
        'attributes' => [
            [
                'apiName' => 'firstNameOriginal',
                'i18n' => ['en_US' => ['name' => 'First Name - Original']],
                'dataType' => 'string',
            ],
            [
                'apiName' => 'firstNameFormatted',
                'i18n' => ['en_US' => ['name' => 'First Name - Formatted']],
                'dataType' => 'string',
            ],
            [
                'apiName' => 'lastNameOriginal',
                'i18n' => ['en_US' => ['name' => 'Last Name - Original']],
                'dataType' => 'string',
            ],
            [
                'apiName' => 'lastNameFormatted',
                'i18n' => ['en_US' => ['name' => 'Last Name - Formatted']],
                'dataType' => 'string',
            ],
        ],
        'fields' => [
            [
                'required' => false,
                'serviceAttribute' => 'FirstNameFormatted',
                'suggestedMarketoAttribute' => 'firstName',
                'description' => ['en_US' => 'The proper-cased First Name result.'],
                'dataType' => 'string',
            ],
            [
                'required' => false,
                'serviceAttribute' => 'LastNameFormatted',
                'suggestedMarketoAttribute' => 'lastName',
                'description' => ['en_US' => 'The proper-cased Last Name result.'],
                'dataType' => 'string',
            ],
        ],
        'userDrivenMapping' => false,
    ],
]);
