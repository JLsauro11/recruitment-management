<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\RecruitmentAlert;
use Illuminate\Support\Facades\Notification;

class RecruitmentNotificationService
{
    public function sendToRecruitmentTeam(array $payload, ?int $excludeUserId = null): void
    {
        $users = User::query()
            ->whereIn('role', ['admin', 'hr'])
            ->where('status', 'active')
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new RecruitmentAlert(array_merge([
            'title' => 'Recruitment Update',
            'message' => '',
            'icon' => 'bi-bell-fill',
            'color' => 'primary',
            'destination' => 'dashboard',
            'parameters' => [],
        ], $payload)));
    }
}
