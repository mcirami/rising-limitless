@php
    $canViewRevenue = false;
    $reportRows = $reporter->fetchReport($dates['startDate'], $dates['endDate']);
    $reportSummary = \App\Support\ReportSummary::fromTotalledReport($reportRows, $canViewRevenue);
    $reportColumns = ['idoffer' => 'Offer ID', 'offer_name' => 'Offer Name', 'Advertiser' => 'Pay Code', 'Clicks' => 'Raw', 'UniqueClicks' => 'Unique', 'Conversions' => 'Sales'];
    $offerCountries = $offerCountries ?? \App\Support\OfferCountryBadges::forOffers(collect(array_slice($reportRows, 0, -1))->map(fn($row) => (object) ['idoffer' => $row['idoffer'], 'offer_name' => $row['offer_name']]));
@endphp
@extends('report.template')

@section('report-title')
    Click Reports
@endsection

@section('report-description', 'Track clicks and conversions across your offers')
@section('report-summary')
    @include('report.partials.summary')
@endsection
@section('report-actions')
    <span class="rl-report-count">{{ number_format($reportSummary['count']) }} {{ $reportSummary['count'] === 1 ? 'offer' : 'offers' }}</span>
@endsection
@section('table-options')
    @include('report.options.dates')
@endsection

@section('table')
    @include('report.partials.offer-performance-table', ['reportCaption' => 'Your offer performance'])
@endsection



@section('footer')
    <script src="{{ $webroot }}js/network-offers.js?v={{ filemtime(public_path('js/network-offers.js')) }}" defer></script>
    <script type="text/javascript">

        $(document).ready(function () {
            $('#mainTable').tablesorter(
                {
                    sortList: [[5, 1]],
                    widgets: ['staticRow'],
                });
        });
    </script>
@endsection
