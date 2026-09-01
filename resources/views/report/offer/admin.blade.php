@php
    use App\Privilege;
    use LeadMax\TrackYourStats\System\Session;
    $userType = (int) Session::userType();
    $canViewRevenue = \App\Support\PayoutVisibility::forCurrentUser();
    $reportRows = $reporter->fetchReport($dates['startDate'], $dates['endDate']);
    $reportSummary = \App\Support\ReportSummary::fromTotalledReport($reportRows, $canViewRevenue);
    $reportColumns = ['idoffer' => 'Offer ID', 'offer_name' => 'Offer Name', 'Clicks' => 'Raw', 'UniqueClicks' => 'Unique', 'Conversions' => 'Conversions'];
    if ($canViewRevenue) {
        $reportColumns['Revenue'] = 'Revenue';
        $reportColumns[$userType === Privilege::ROLE_ADMIN ? 'Advertiser' : 'EPC'] = $userType === Privilege::ROLE_ADMIN ? 'Advertiser' : 'EPC';
    }
@endphp
@extends('report.template')
@section('report-title', 'Offer Reports')
@section('report-description', $canViewRevenue ? 'Track clicks, conversions, and revenue across your offers' : 'Track clicks and conversions across your offers')
@section('report-summary')
    @include('report.partials.summary')
@endsection
@section('table-options')
    @include('report.options.dates')
@endsection
@section('report-actions')
    @if($userType === Privilege::ROLE_GOD || $userType === Privilege::ROLE_ADMIN)
        <a class="rl-button rl-report-export" href="/report/offer-data/export?{{ http_build_query(array_merge(request()->only(['adminLogin']), ['d_from' => $startDate, 'd_to' => $endDate, 'dateSelect' => $dateSelect])) }}"><i class="fas fa-download" aria-hidden="true"></i> Export Data</a>
    @endif
    <span class="rl-report-count">{{ number_format($reportSummary['count']) }} {{ $reportSummary['count'] === 1 ? 'offer' : 'offers' }}</span>
@endsection
@section('table')
    @include('report.partials.performance-table', ['reportCaption' => 'Offer performance'])
@endsection
@section('footer')
    <script>
        $(function () { $('#mainTable').tablesorter({sortList: [[4, 1]], widgets: ['staticRow']}); });
    </script>
@endsection
