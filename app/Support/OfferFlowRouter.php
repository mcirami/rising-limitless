<?php

namespace App\Support;

use App\OfferRoutingFlow;
use Illuminate\Support\Facades\Schema;

final class OfferFlowRouter
{
    public static function forEntry(int $offerId): ?OfferRoutingFlow
    {
        // Allow code deployment before the additive migration is applied.
        if (!Schema::hasTable('offer_routing_flows')) return null;

        return OfferRoutingFlow::where('entry_offer_id', $offerId)->where('is_active', true)->first();
    }

    /** Pure country selection, also used by the editor's test endpoint. */
    public static function resolve(array $steps, int $fallbackOfferId, ?string $country): array
    {
        $country = strtoupper(trim($country ?? ''));
        $skipped = [];
        foreach ($steps as $index => $step) {
            if (!empty($step['allow_all_countries']) || in_array($country, $step['countries'], true)) {
                return ['offer_id' => (int) $step['offer_id'], 'position' => $index + 1, 'skipped' => $skipped, 'fallback' => false];
            }
            $skipped[] = $index + 1;
        }
        return ['offer_id' => $fallbackOfferId, 'position' => null, 'skipped' => $skipped, 'fallback' => true];
    }
}
