<?php

return [

    /*
    |--------------------------------------------------------------------------
    | REST API
    |--------------------------------------------------------------------------
    |
    | The RestModule URL is entered on the login page and kept in the session,
    | so one panel can manage any cloud. It is also remembered in a cookie to
    | fill in the login form next time.
    |
    */

    'timeout' => (int) env('REXLCLOUD_API_TIMEOUT', 10),

];
