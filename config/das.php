<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mother Admin Bootstrap Credentials
    |--------------------------------------------------------------------------
    |
    | Used only by the `das:bootstrap-mother-admin` Artisan command to create
    | the initial highest-privilege admin account. When any of these are left
    | unset, the command prompts for them interactively instead.
    |
    */

    'mother_admin' => [
        'name' => env('MOTHER_ADMIN_NAME'),
        'email' => env('MOTHER_ADMIN_EMAIL'),
        'password' => env('MOTHER_ADMIN_PASSWORD'),
    ],

];
