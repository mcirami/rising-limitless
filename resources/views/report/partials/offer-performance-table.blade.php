@php
    $detailRows = array_slice($reportRows, 0, -1);
    $role = (int) \LeadMax\TrackYourStats\System\Session::userType();
    $params = request()->only(['d_from', 'd_to', 'dateSelect', 'role', 'adminLogin']);
    if (!isset($params['d_from'], $params['d_to'])) {
        $params['d_from'] = $dates['originalStart'] ?? '';
        $params['d_to'] = $dates['originalEnd'] ?? '';
        $params['dateSelect'] = $params['dateSelect'] ?? '';
    }
    $query = http_build_query($params);
@endphp
<table class="table table_01 tablesorter rl-performance-table" id="mainTable" data-performance-table>
    <caption class="sr-only">{{ $reportCaption }} for the selected date range</caption>
    <thead><tr>
        @foreach($reportColumns as $key => $label)<th scope="col" data-report-field="{{ $key }}">{{ $label }}</th>@endforeach
    </tr></thead>
    <tbody>
        @forelse($detailRows as $row)
            @php
                foreach ($reportColumns as $field => $label) $row[$field] = $row[$field] ?? '';
                $offer = (object) ['idoffer' => $row['idoffer']];
                $countryInfo = $offerCountries[$row['idoffer']] ?? \App\Support\OfferCountryBadges::present(html_entity_decode((string) $row['offer_name'], ENT_QUOTES, 'UTF-8'));
            @endphp
            <tr>
                @foreach($reportColumns as $field => $label)
                    @if($field === 'offer_name')
                        <td class="rl-report-offer" data-report-offer-cell>
                            @if(in_array($role, [\App\Privilege::ROLE_GOD, \App\Privilege::ROLE_MANAGER], true))
                                <a href="/offer_update.php?idoffer={{ $row['idoffer'] }}">{{ $countryInfo['name'] }}</a>
                            @else
                                <span class="rl-offer-name">{{ $countryInfo['name'] }}</span>
                            @endif
                            @include('offer.partials.country-meta', ['showOfferId' => false, 'countryLabel' => 'Available GEOs', 'agentLayout' => true])
                        </td>
                    @elseif($field === 'Advertiser')
                        <td><span class="rl-advertiser-name">{{ $row[$field] ?: '—' }}</span></td>
                    @elseif($field === 'Conversions' && is_numeric($row[$field]) && (float) $row[$field] > 0 && is_numeric($row['idoffer']))
                        <td><a class="load_click" href="{{ $role === \App\Privilege::ROLE_AFFILIATE ? '/user/' . \LeadMax\TrackYourStats\System\Session::userID() . '/' . $row['idoffer'] . '/conversions-by-country' : '/report/offer/' . $row['idoffer'] . '/user-conversions' }}?{{ $query }}">{{ $row[$field] }}</a></td>
                    @else
                        <td>{!! $row[$field] !!}</td>
                    @endif
                @endforeach
            </tr>
        @empty
            <tr class="static rl-report-empty"><td colspan="{{ count($reportColumns) }}"><i class="far fa-chart-bar" aria-hidden="true"></i><strong>No activity in this date range</strong><span>Try another date range to see your report.</span></td></tr>
        @endforelse
    </tbody>
    @if($reportSummary['count'])
        <tfoot><tr class="static">
            @foreach($reportColumns as $key => $label)
                <td>@if($loop->first)—@elseif($loop->index === 1) TOTALS @else{{ strip_tags((string) (end($reportRows)[$key] ?? '')) }}@endif</td>
            @endforeach
        </tr></tfoot>
    @endif
</table>
