<?php

if (! function_exists('sendWabaTemplate')) {

    function sendWabaTemplate($phone, $template, $variables = [])
    {
        // sesuaikan dengan logic pengiriman yang kamu pakai
        // contoh minimal:
        \Illuminate\Support\Facades\Http::post(config('waba.api_url'), [
            'phone' => $phone,
            'template' => $template->name,
            'variables' => $variables
        ]);
    }
}
