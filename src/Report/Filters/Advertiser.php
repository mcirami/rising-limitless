<?php

namespace LeadMax\TrackYourStats\Report\Filters;

use Illuminate\Support\Facades\DB;

class Advertiser implements Filter
{
    public function filter($data)
    {
        $offerIds = collect($data)
            ->pluck('idoffer')
            ->filter(fn ($id) => is_numeric($id))
            ->unique()
            ->values();

        $advertisers = $offerIds->isEmpty()
            ? collect()
            : DB::table('offer')
                ->leftJoin('campaigns', 'campaigns.id', '=', 'offer.campaign_id')
                ->whereIn('offer.idoffer', $offerIds)
                ->pluck('campaigns.name', 'offer.idoffer');

        foreach ($data as &$row) {
            $offerId = $row['idoffer'] ?? null;
            $row['Advertiser'] = is_numeric($offerId)
                ? ($advertisers->get($offerId) ?: '—')
                : '';
        }
        unset($row);

        return $data;
    }
}
