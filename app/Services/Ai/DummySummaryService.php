<?php

namespace App\Services\Ai;

class DummySummaryService
{
    public function summarize(array $messages): string
    {
        if (empty($messages)) {
            return 'Belum terdapat percakapan yang dapat dirangkum.';
        }

        $customerText = collect($messages)
            ->where('role', 'customer')
            ->pluck('content')
            ->filter()
            ->take(5)
            ->implode(' ');

        if (! $customerText) {
            return 'Percakapan singkat tanpa isu utama dari pelanggan.';
        }

        return 'Pelanggan menyampaikan beberapa pesan terkait permintaan atau keluhan. '
            . 'Topik utama percakapan mencakup: '
            . mb_substr($customerText, 0, 300) . '.';
    }
}
