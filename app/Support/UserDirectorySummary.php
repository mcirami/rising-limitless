<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class UserDirectorySummary
{
    /** The caller must supply the same authorized user scope as the directory. */
    public static function forUsers(Builder $users, string $monthStart): array
    {
        $nextMonth = Carbon::parse($monthStart)->addMonth()->startOfMonth()->toDateTimeString();
        $summary = (clone $users)
            ->leftJoin('privileges as directory_roles', 'directory_roles.rep_idrep', '=', 'rep.idrep')
            ->selectRaw('COUNT(DISTINCT rep.idrep) as total')
            ->selectRaw('COUNT(DISTINCT CASE WHEN rep.status = 1 THEN rep.idrep END) as active')
            ->selectRaw('COUNT(DISTINCT CASE WHEN rep.status = 0 THEN rep.idrep END) as inactive')
            ->selectRaw('COUNT(DISTINCT CASE WHEN directory_roles.is_rep = 1 THEN rep.idrep END) as agents')
            ->selectRaw('COUNT(DISTINCT CASE WHEN directory_roles.is_manager = 1 THEN rep.idrep END) as managers')
            ->selectRaw('COUNT(DISTINCT CASE WHEN rep.rep_timestamp >= ? AND rep.rep_timestamp < ? THEN rep.idrep END) as new_this_month', [$monthStart, $nextMonth])
            ->first();

        return array_map('intval', $summary->getAttributes());
    }
}
