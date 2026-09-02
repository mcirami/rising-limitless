<span class="rl-offer-meta {{ ($agentLayout ?? false) ? 'is-agent' : '' }}">
    @if($showOfferId ?? true)<span class="rl-offer-id">#{{ $offer->idoffer }}</span>@endif
    <span class="rl-country-details">
    @if($countryInfo['countries'])
        @if($countryLabel ?? false)<span class="rl-country-label">{{ $countryLabel }}</span>@endif
        <span class="rl-offer-countries" aria-label="{{ $countryInfo['mode'] === 'excluded' ? 'Excluded countries' : ($countryInfo['source'] === 'rules' ? 'Countries in active allow rule' : 'Countries from offer title') }}">
            @if($countryInfo['mode'] === 'excluded')<span class="rl-country-note">Excludes</span>@endif
            @if($countryInfo['source'] === 'title' && $countryInfo['note'])<span class="rl-country-note">Title:</span>@endif
            @foreach($countryInfo['countries'] as $code => $countryName)
                <span class="rl-country-badge" @if($loop->index >= 12) data-geo-extra hidden @endif title="{{ $countryName }} · {{ $countryInfo['source'] === 'title' ? 'From offer title; check rules for restrictions' : ($countryInfo['mode'] === 'excluded' ? 'Blocked by country rule' : 'Active country allow rule') }}">{{ $code }}</span>
            @endforeach
            @if(count($countryInfo['countries']) > 12)
                <button type="button" class="rl-geo-toggle" data-geo-toggle data-collapsed-label="Show all {{ count($countryInfo['countries']) }}" aria-expanded="false">Show all {{ count($countryInfo['countries']) }}</button>
            @endif
        </span>
    @endif
    @if($countryInfo['note'])
        <span class="rl-country-note rl-country-rule-note">{{ $countryInfo['note'] }}</span>
    @endif
    </span>
</span>
