<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Support\IamLogger;

class AutoSuspendUnverifiedUsers extends Command
{
    protected $signature = 'users:auto-suspend-unverified';
    protected $description = 'Auto suspend users whose email verification grace period has expired';

    public function handle(): int
    {
        User::query()
            ->whereNull('email_verified_at')
            ->whereNotNull('email_verify_grace_until')
            ->where('email_verify_grace_until', '<', now())
            ->where('is_active', true)
            ->get()
            ->each(function (User $user) {

                $user->update([
                    'is_active' => false,
                ]);

                IamLogger::log(
                    'user_auto_suspended_unverified',
                    $user->id,
                    null,
                    [
                        'reason' => 'email_verification_grace_expired',
                        'suspended_at' => now(),
                    ]
                );
            });

        $this->info('Auto suspend unverified users executed.');
        return Command::SUCCESS;
    }
}
