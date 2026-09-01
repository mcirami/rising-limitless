@extends('layouts.master')
@section('content')
@php
    $session = \LeadMax\TrackYourStats\System\Session::class;
    $role = (int) $session::userType();
    $permissions = $session::permissions();
    $isAgent = $role === 3;
    $showPayout = \App\Support\PayoutVisibility::forCurrentUser();
    $showAccess = !$isAgent && in_array($role, [0, 1]) && $permissions->can('edit_affiliates');
    $typeNames = [0 => 'CPA', 1 => 'CPC', 2 => 'Blacklisted', 3 => 'Pending'];
    $offerList = collect($offers);
    $payouts = $showPayout ? $offerList->map(fn($offer) => (float) $offer->payout) : collect();
    $availableTypes = $offerList->pluck('offer_type')->unique()->values();
    $inactive = request('showInactive', 0) == 1;
    $urlIndex = max(0, (int) request('url', 0));
    $offerDomain = $urls[$urlIndex] ?? ($urls[0] ?? request()->getHttpHost());
    $contextUrl = function ($path) {
        return $path . (request()->has('adminLogin') ? (str_contains($path, '?') ? '&' : '?') . 'adminLogin=1' : '');
    };
    $columns = ($isAgent ? 5 : 6) + ($showPayout ? 1 : 0) + ($showAccess ? 1 : 0);
@endphp
<div class="right_panel">
    <div class="rl-page-heading"><div><h1>Offers</h1><p>{{ $showPayout ? "Manage your network's offer inventory and payout rules" : "Browse your network's offer inventory" }}</p></div>
        @if($permissions->can('create_offers'))<a class="rl-button rl-primary" href="{{ $contextUrl('/offer_add.php') }}"><span aria-hidden="true">＋</span> Create New Offer</a>@endif
    </div>
    <div class="rl-metrics">
        <div class="rl-metric"><span class="rl-metric-label">Available Offers</span><strong>{{ $offerList->count() }}</strong><small>{{ $inactive ? 'Inactive' : 'Active' }} offers in your account</small></div>
        <div class="rl-metric is-green"><span class="rl-metric-label">{{ $inactive ? 'Inactive' : 'Active' }}</span><strong>{{ $offerList->where('status', $inactive ? 0 : 1)->count() }}</strong><small>In the current inventory</small></div>
        @if($showPayout)
            <div class="rl-metric is-coral"><span class="rl-metric-label">Avg Payout</span><strong>${{ number_format($payouts->avg() ?? 0, 2) }}</strong><small>${{ number_format($payouts->min() ?? 0, 2) }} – ${{ number_format($payouts->max() ?? 0, 2) }} range</small></div>
        @elseif($isAgent)
            <div class="rl-metric is-coral"><span class="rl-metric-label">Available GEOs</span><strong>{{ $availableGeoCount ?? 0 }}</strong><small>Across your available offers</small></div>
        @else
            <div class="rl-metric is-coral"><span class="rl-metric-label">Advertisers</span><strong>{{ $offerList->pluck('campaign_id')->filter()->unique()->count() }}</strong><small>Across available offers</small></div>
        @endif
        <div class="rl-metric is-purple"><span class="rl-metric-label">Offer Types</span><strong>{{ $availableTypes->count() }}</strong><small>{{ $availableTypes->map(fn($type) => $typeNames[$type] ?? 'Other')->implode(' · ') ?: 'No offers available' }}</small></div>
    </div>
    @if($isAgent)
        <div class="rl-card rl-card-body"><label for="offer-domain">Tracking domain</label><select id="offer-domain" data-offer-domain>
            @foreach($urls as $index => $domain)<option value="{{ $index }}" @selected($domain === $offerDomain)>{{ $domain }}</option>@endforeach
        </select><p class="rl-note">Copy your offer link below. Add up to five tracking variables: sub1, sub2, sub3, sub4, and sub5.</p></div>
    @endif
    <section class="rl-card" data-offer-table>
        <div class="rl-toolbar">
            <label class="rl-search"><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="searchBox" data-offer-search placeholder="Search offers, IDs, advertisers…" aria-label="Search offers"></label>
            <select data-offer-type aria-label="Filter by offer type"><option value="">All types</option>@foreach($availableTypes as $type)<option value="{{ $type }}">{{ $typeNames[$type] ?? 'Other' }}</option>@endforeach</select>
            <div class="rl-toolbar-end">
                <label for="offers-page-size">Rows</label><select id="offers-page-size" data-offer-page-size><option>20</option><option>50</option><option>100</option></select>
                @if(!$isAgent)<a class="rl-button" href="{{ request()->fullUrlWithQuery(['showInactive' => $inactive ? 0 : 1]) }}">{{ $inactive ? 'Show Active' : 'Show Inactive' }}</a>@endif
            </div>
        </div>
        <div class="rl-table-scroll">
            <table class="table" id="mainTable">
                <thead><tr>
                    <th aria-sort="ascending"><button class="rl-sort" data-offer-sort="name">Offer ↕</button></th><th>Type</th>
                    @if($showAccess)<th>Access</th>@endif
                    @if($isAgent)<th>Offer Link</th>@endif
                    @if($showPayout)<th><button class="rl-sort" data-offer-sort="payout">Payout ↕</button></th>@endif
                    <th>Advertiser</th><th>Status</th>
                    @if(!$isAgent)<th><button class="rl-sort" data-offer-sort="created">Created ↕</button></th><th>Actions</th>@endif
                </tr></thead>
                <tbody id="offers_container">
                @foreach($offerList as $offer)
                    @php
                        $name = html_entity_decode($offer->offer_name, ENT_QUOTES, 'UTF-8');
                        $countryInfo = $offerCountries[$offer->idoffer] ?? \App\Support\OfferCountryBadges::present($name);
                        $payout = (float) ($isAgent ? data_get($offer, 'pivot.payout', 0) : $offer->payout);
                        $trackingLink = 'https://' . preg_replace('#^https?://#', '', rtrim($offerDomain, '/')) . '/?repid=' . $session::userID() . '&offerid=' . $offer->idoffer . '&sub1=';
                    @endphp
                    <tr data-offer-row data-name="{{ $countryInfo['name'] }}" data-search="{{ $name . ' ' . $offer->idoffer . ' ' . $offer->campaign_name . ' ' . implode(' ', array_keys($countryInfo['countries'])) . ' ' . implode(' ', $countryInfo['countries']) }}" data-type="{{ $offer->offer_type }}" @if($showPayout) data-payout="{{ $payout }}" @endif data-created="{{ $offer->offer_timestamp }}">
                        <td>
                            @if($isAgent)
                                <span class="rl-offer-id is-leading">#{{ $offer->idoffer }}</span>
                                <span class="rl-offer-name">{{ $countryInfo['name'] }}</span>
                                @include('offer.partials.country-meta', ['showOfferId' => false, 'countryLabel' => 'Available GEOs', 'agentLayout' => true])
                            @else
                                <span class="rl-offer-name">{{ $countryInfo['name'] }}</span>
                                @include('offer.partials.country-meta')
                            @endif
                        </td>
                        <td><span class="rl-badge is-type">{{ $typeNames[$offer->offer_type] ?? 'Other' }}</span></td>
                        @if($showAccess)<td><a class="rl-button" href="{{ $contextUrl('/offer_access.php?id=' . $offer->idoffer) }}"><i class="fas fa-lock" aria-hidden="true"></i> Affiliate Access</a></td>@endif
                        @if($isAgent)<td><button type="button" class="rl-button" data-copy-text="{{ $trackingLink }}">Copy My Link</button></td>@endif
                        @if($showPayout)<td class="rl-money">${{ number_format($payout, 2) }}</td>@endif
                        <td>{{ $offer->campaign_name ?: '—' }}</td>
                        <td><span class="rl-badge {{ (int) $offer->status === 1 ? 'is-active' : 'is-inactive' }}">● {{ (int) $offer->status === 1 ? 'Active' : 'Inactive' }}</span></td>
                        @if(!$isAgent)
                            <td class="rl-date">{{ substr((string) $offer->offer_timestamp, 0, 10) }}</td>
                            <td class="action_column">
                                @if($permissions->can('create_offers'))<a class="btn btn-sm" href="{{ $contextUrl('/offer_update.php?idoffer=' . $offer->idoffer) }}">Edit</a>@endif
                                @if($permissions->can('edit_offer_rules'))<a class="btn btn-sm" href="{{ $contextUrl('/offer_edit_rules.php?offid=' . $offer->idoffer) }}">Rules</a>@endif
                                <a class="btn btn-sm" href="{{ $contextUrl('/offer_details.php?idoffer=' . $offer->idoffer) }}">View</a>
                                @if($role === 0)
                                    <a class="btn btn-sm" href="{{ $contextUrl('/offer/' . $offer->idoffer . '/dupe') }}">Duplicate</a>
                                    <a class="btn btn-sm btn-danger" data-delete-offer href="{{ $contextUrl('/offer/' . $offer->idoffer . '/delete') }}">Delete</a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
                <tr data-offer-empty hidden><td colspan="{{ $columns }}" class="rl-empty">No offers match your search. Try another name or type.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="rl-table-footer"><span data-offer-count role="status">{{ $offerList->count() }} offers</span><nav class="rl-pagination" data-offer-pagination aria-label="Offer pagination"></nav></div>
    </section>
    @if($isAgent && isset($requestableOffers) && count($requestableOffers))
        <section class="rl-card"><header class="rl-card-header"><h2>Requestable Offers</h2></header><div class="rl-table-scroll"><table class="table"><thead><tr><th>Offer</th><th>Access</th></tr></thead><tbody>
        @foreach($requestableOffers as $offer)
            @php $countryInfo = $offerCountries[$offer->idoffer] ?? \App\Support\OfferCountryBadges::present(html_entity_decode($offer->offer_name, ENT_QUOTES, 'UTF-8')); @endphp
            <tr><td><span class="rl-offer-id is-leading">#{{ $offer->idoffer }}</span><span class="rl-offer-name">{{ $countryInfo['name'] }}</span>@include('offer.partials.country-meta', ['showOfferId' => false, 'countryLabel' => 'Available GEOs', 'agentLayout' => true])</td><td><button class="rl-button" data-request-offer="{{ $contextUrl('/offer/' . $offer->idoffer . '/request') }}">Request Offer</button></td></tr>
        @endforeach
        </tbody></table></div></section>
    @endif
</div>
@endsection
@section('footer')
<script src="{{ $webroot }}js/network-offers.js?v={{ filemtime(public_path('js/network-offers.js')) }}" defer></script>
@endsection
