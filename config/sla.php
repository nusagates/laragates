<?php

return [

    'tickets' => [

        'pending_to_ongoing' => [
            'max_minutes' => 15,
        ],

        'ongoing_to_closed' => [
            'low'    => 24 * 60, // 24 jam
            'medium' => 8 * 60,  // 8 jam
            'high'   => 2 * 60,  // 2 jam
        ],

    ],

];
