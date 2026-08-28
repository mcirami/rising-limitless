<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use LeadMax\TrackYourStats\Offer\Rules\Geo;

final class OfferCountryBadges
{
    /** One read for the already-authorized inventory, never one query per offer. */
    public static function forOffers(iterable $offers): array
    {
        $offers = collect($offers);
        $ids = $offers->pluck('idoffer')->filter()->unique()->values()->all();
        if (!$ids) return [];

        $rules = DB::table('rule')
            ->leftJoin('geo_rule', 'geo_rule.rule_idrule', '=', 'rule.idrule')
            ->leftJoin('country_list', 'country_list.geo_rule_idgeo_rule', '=', 'geo_rule.idgeo_rule')
            ->whereIn('rule.offer_idoffer', $ids)
            ->where('rule.type', 'geo')->where('rule.is_active', 1)
            ->orderBy('rule.idrule')->orderBy('country_list.country_code')
            ->get(['rule.offer_idoffer', 'rule.idrule', 'rule.deny', 'country_list.country_code'])
            ->groupBy('offer_idoffer');

        return $offers->mapWithKeys(fn($offer) => [
            $offer->idoffer => self::present(html_entity_decode($offer->offer_name, ENT_QUOTES, 'UTF-8'), $rules->get($offer->idoffer, collect())->all()),
        ])->all();
    }

    public static function present(string $name, array $ruleRows = []): array
    {
        $title = self::parseTitle($name);
        $result = ['name' => $name, 'countries' => [], 'source' => '', 'mode' => '', 'note' => ''];
        if (!$ruleRows) {
            return array_replace($result, $title, ['source' => $title['countries'] ? 'title' : '', 'mode' => 'allowed']);
        }

        $rules = collect($ruleRows)->groupBy('idrule');
        // The legacy engine short-circuits its rules. Keep title countries distinct from eligibility.
        if ($rules->count() !== 1) {
            return array_replace($result, $title, [
                'source' => $title['countries'] ? 'title' : '',
                'mode' => 'listed',
                'note' => 'Multiple GEO rules',
            ]);
        }

        $countries = [];
        foreach ($ruleRows as $row) {
            $code = strtoupper(trim((string) data_get($row, 'country_code')));
            if (!isset(Geo::$countries[$code])) return array_replace($result, ['note' => 'Custom GEO rule']);
            $countries[$code] = Geo::$countries[$code];
        }
        $denied = (int) data_get($ruleRows[0], 'deny') === 1;
        return array_replace($result, [
            'name' => $denied ? $name : $title['name'],
            'countries' => $countries,
            'source' => 'rules',
            'mode' => $denied ? 'excluded' : 'allowed',
        ]);
    }

    /** Extract complete uppercase comma/slash lists; ordinary words and unknown codes stay intact. */
    private static function parseTitle(string $name): array
    {
        $countries = [];
        $pattern = '~(?<![A-Za-z0-9/,])([A-Z]{2}(?:\s*[/,]\s*[A-Z]{2})+)(?![A-Za-z0-9/,])(?:\s+[Oo][Nn][Ll][Yy]\b)?~';
        preg_match_all($pattern, $name, $matches, PREG_OFFSET_CAPTURE);
        $clean = $name;
        $remove = [];
        foreach ($matches[0] as $index => [$match, $offset]) {
            $before = substr($name, 0, $offset);
            $after = substr($name, $offset + strlen($match));
            if (preg_match('~\b(?:no|not|except|exclud(?:e|es|ed|ing)|blocked)\s*(?:countries|geos)?\s*[:=-]?\s*$~i', $before)
                || preg_match('~^\s*(?:excluded|blocked|not allowed)\b~i', $after)) continue;
            $list = [];
            foreach (preg_split('~\s*[/,]\s*~', $matches[1][$index][0]) as $code) {
                $code = $code === 'UK' ? 'GB' : $code;
                if (!isset(Geo::$countries[$code])) { $list = []; break; }
                $list[$code] = Geo::$countries[$code];
            }
            if (!$list) continue;
            $countries += $list;
            $remove[] = [$offset, strlen($match)];
        }
        foreach (array_reverse($remove) as [$offset, $length]) $clean = substr_replace($clean, '', $offset, $length);
        if ($remove) {
            $clean = preg_replace('~\(\s*\)|\[\s*\]~', '', $clean);
            $clean = preg_replace('~(?:\s*[-–—]\s*){2,}~u', ' - ', $clean);
            $clean = preg_replace('~\s+[-–—]\s+(?=\()~u', ' ', $clean);
            $clean = preg_replace('~\s+~', ' ', $clean);
            $clean = preg_replace('~^[\s\-–—]+|[\s\-–—]+$~u', '', $clean);
        }
        return ['name' => $clean ?: $name, 'countries' => $countries];
    }
}
