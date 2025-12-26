<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Circuit Breaker
    |--------------------------------------------------------------------------
    */

    'circuit_breaker' => [
        'failure_threshold' => 3,      // gagal berapa kali → OPEN
        'cooldown_seconds'  => 60,     // berapa detik sebelum retry
    ],

];
