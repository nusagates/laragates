<?php

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Auth;

class AiGovernance
{
    /**
     * Global AI toggle
     */
    public function isEnabled(): bool
{
    return AiSetting::first()?->enabled === true;
}


    /**
     * Role-based access
     */
    public function isRoleAllowed(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return in_array(
            $user->role,
            config('ai_governance.allowed_roles', [])
        );
    }

    /**
     * Daily quota per user
     */
    public function hasQuota(): bool
{
    $userId = Auth::id();
    if (!$userId) {
        return false;
    }

    $setting = AiSetting::first();
    if (!$setting) {
        return false;
    }

    $usageToday = AiRequestLog::where('user_id', $userId)
        ->whereDate('created_at', now())
        ->count();

    return $usageToday < $setting->daily_quota;
}

    /**
     * Final decision gate
     */
    public function canUseAi(): bool
    {
        return $this->isEnabled()
            && $this->isRoleAllowed()
            && $this->hasQuota();
    }
}
