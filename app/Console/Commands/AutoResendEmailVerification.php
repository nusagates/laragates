<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AutoResendEmailVerification extends Command
{
    protected $signature = 'email:auto-resend-verification';
    protected $description = 'Auto resend email verification for unverified users';

    public function handle(): int
    {
        User::query()
            ->whereNull('email_verified_at')
            ->whereNotNull('email_verify_grace_until')
            ->get()
            ->each(function (User $user) {

                if (!$user->canAutoResendVerification()) {
                    return;
                }

                $user->sendEmailVerificationNotification();

                $user->increment('verification_resend_count');

                $user->update([
                    'last_verification_sent_at' => now(),
                ]);
            });

        $this->info('Auto resend email verification executed.');
        return Command::SUCCESS;
    }
}
