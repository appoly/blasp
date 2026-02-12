<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | The default detection driver. 'regex' provides full obfuscation
    | detection. 'pattern' is faster but only matches exact words.
    |
    */
    'default' => env('BLASP_DRIVER', 'regex'),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language to use for profanity detection.
    |
    */
    'language' => env('BLASP_LANGUAGE', 'english'),

    // Backward compat alias
    'default_language' => env('BLASP_LANGUAGE', 'english'),

    /*
    |--------------------------------------------------------------------------
    | Mask Character
    |--------------------------------------------------------------------------
    |
    | The character used to mask detected profanities.
    |
    */
    'mask' => '*',

    // Backward compat alias
    'mask_character' => '*',

    /*
    |--------------------------------------------------------------------------
    | Minimum Severity
    |--------------------------------------------------------------------------
    |
    | The minimum severity level to detect. Words below this severity
    | will be ignored. Options: mild, moderate, high, extreme
    |
    */
    'severity' => 'mild',

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | When enabled, ProfanityDetected events will be fired automatically
    | when profanity is found during a check.
    |
    */
    'events' => false,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'driver' => env('BLASP_CACHE_DRIVER'),
        'ttl' => 86400,
    ],

    // Backward compat alias
    'cache_driver' => env('BLASP_CACHE_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'action' => 'reject',
        'fields' => ['*'],
        'except' => ['password', 'email', '_token'],
        'severity' => 'mild',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how the Blaspable trait behaves on Eloquent models.
    | 'sanitize' replaces profanity with the mask character.
    | 'reject' throws a ProfanityRejectedException instead of saving.
    |
    */
    'model' => [
        'mode' => env('BLASP_MODEL_MODE', 'sanitize'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Character Separators
    |--------------------------------------------------------------------------
    */
    'separators' => [
        '@', '#', '%', '&', '_', ';', "'", '"', ',', '~', '`', '|',
        '!', '$', '^', '*', '(', ')', '-', '+', '=', '{', '}',
        '[', ']', ':', '<', '>', '?', '.', '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Character Substitutions
    |--------------------------------------------------------------------------
    */
    'substitutions' => [
        '/a/' => ['a', '4', '@', 'Á', 'á', 'À', 'Â', 'à', 'Â', 'â', 'Ä', 'ä', 'Ã', 'ã', 'Å', 'å', 'æ', 'Æ', 'α', 'Δ', 'Λ', 'λ'],
        '/b/' => ['b', '8', '\\', '3', 'ß', 'Β', 'β'],
        '/c/' => ['c', 'Ç', 'ç', 'ć', 'Ć', 'č', 'Č', '¢', '€', '<', '(', '{', '©'],
        '/d/' => ['d', '\\', ')', 'Þ', 'þ', 'Ð', 'ð'],
        '/e/' => ['e', '3', '€', 'È', 'è', 'É', 'é', 'Ê', 'ê', 'ë', 'Ë', 'ē', 'Ē', 'ė', 'Ė', 'ę', 'Ę', '∑'],
        '/f/' => ['f', 'ƒ'],
        '/g/' => ['g', '6', '9'],
        '/h/' => ['h', 'Η'],
        '/i/' => ['i', '!', '|', ']', '[', '1', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'ī', 'Ī', 'į', 'Į'],
        '/j/' => ['j'],
        '/k/' => ['k', 'Κ', 'κ'],
        '/l/' => ['l', '!', '|', ']', '[', '£', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ł', 'Ł'],
        '/m/' => ['m'],
        '/n/' => ['n', 'η', 'Ν', 'Π', 'ñ', 'Ñ', 'ń', 'Ń'],
        '/o/' => ['o', '0', 'Ο', 'ο', 'Φ', '¤', '°', 'ø', 'ô', 'Ô', 'ö', 'Ö', 'ò', 'Ò', 'ó', 'Ó', 'œ', 'Œ', 'ø', 'Ø', 'ō', 'Ō', 'õ', 'Õ'],
        '/p/' => ['p', 'ρ', 'Ρ', '¶', 'þ'],
        '/q/' => ['q'],
        '/r/' => ['r', '®'],
        '/s/' => ['s', '5', '\$', '§', 'ß', 'Ś', 'ś', 'Š', 'š'],
        '/t/' => ['t', 'Τ', 'τ'],
        '/u/' => ['u', 'υ', 'µ', 'û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū', '@', '*'],
        '/v/' => ['v', 'υ', 'ν'],
        '/w/' => ['w', 'ω', 'ψ', 'Ψ'],
        '/x/' => ['x', 'Χ', 'χ'],
        '/y/' => ['y', '¥', 'γ', 'ÿ', 'ý', 'Ÿ', 'Ý'],
        '/z/' => ['z', 'Ζ', 'ž', 'Ž', 'ź', 'Ź', 'ż', 'Ż'],
    ],

    /*
    |--------------------------------------------------------------------------
    | False Positives
    |--------------------------------------------------------------------------
    */
    'false_positives' => [
        'hello', 'scunthorpe', 'cockburn', 'penistone', 'lightwater',
        'assume', 'bass', 'class', 'compass', 'pass',
        'dickinson', 'middlesex', 'cockerel', 'butterscotch', 'blackcock',
        'countryside', 'arsenal', 'flick', 'flicker', 'analyst',
        'cocktail', 'musicals hit', 'is hit', 'blackcocktail', 'its not',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Allow List
    |--------------------------------------------------------------------------
    |
    | Words in this list will never be flagged as profanity.
    |
    */
    'allow' => [],

    /*
    |--------------------------------------------------------------------------
    | Global Block List
    |--------------------------------------------------------------------------
    |
    | Additional words to always flag as profanity.
    |
    */
    'block' => [],

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility: Profanities
    |--------------------------------------------------------------------------
    |
    | Basic profanity list for backward compatibility.
    | Full lists are in config/languages/*.php
    |
    */
    'profanities' => [
        'fuck', 'shit', 'damn', 'bitch', 'ass', 'hell',
    ],

];
