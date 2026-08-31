<?php

namespace App\Support;

use App\Click;
use App\Privilege;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardTrafficSnapshot
{
    public static function forUser(int $userId, int $role, ?Carbon $date = null): Collection
    {
        $date = ($date ?: Carbon::now())->copy();
        $userIds = self::visibleUserIds($userId, $role);
        if ($userIds->isEmpty()) {
            return collect();
        }

        $start = $date->copy()->startOfDay()->toDateTimeString();
        $end = $date->copy()->endOfDay()->toDateTimeString();
        $sales = DB::table('conversions')
            ->join('clicks as sale_clicks', 'sale_clicks.idclicks', '=', 'conversions.click_id')
            ->whereIn('sale_clicks.rep_idrep', $userIds)
            ->whereBetween('conversions.timestamp', [$start, $end])
            ->groupBy('sale_clicks.offer_idoffer')
            ->selectRaw('sale_clicks.offer_idoffer AS offer_id, COUNT(conversions.id) AS total_sales');

        return DB::table('clicks')
            ->join('offer', 'offer.idoffer', '=', 'clicks.offer_idoffer')
            ->leftJoinSub($sales, 'sales', function ($join) {
                $join->on('sales.offer_id', '=', 'clicks.offer_idoffer');
            })
            ->whereIn('clicks.rep_idrep', $userIds)
            ->whereBetween('clicks.first_timestamp', [$start, $end])
            ->where('clicks.click_type', '!=', Click::TYPE_BLACKLISTED)
            ->groupBy('clicks.offer_idoffer', 'offer.offer_name', 'sales.total_sales')
            ->selectRaw('clicks.offer_idoffer AS offer_id, offer.offer_name, SUM(CASE WHEN clicks.click_type = ? THEN 1 ELSE 0 END) AS unique_clicks, COALESCE(sales.total_sales, 0) AS total_sales', [Click::TYPE_UNIQUE])
            ->orderByDesc('unique_clicks')
            ->get();
    }

    public static function visibleUserIds(int $userId, int $role): Collection
    {
        if ($role === Privilege::ROLE_AFFILIATE) {
            return collect([$userId]);
        }
        if ($role !== Privilege::ROLE_MANAGER) {
            return collect();
        }
        $manager = User::query()->find($userId);
        if (!$manager) {
            return collect();
        }
        return User::query()
            ->where('lft', '>', $manager->lft)
            ->where('rgt', '<', $manager->rgt)
            ->pluck('idrep');
    }
}
