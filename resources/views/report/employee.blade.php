@php
    use App\Privilege;
    use LeadMax\TrackYourStats\System\Session;
    $userType = (int) Session::userType();
    $canViewRevenue = \App\Support\PayoutVisibility::forCurrentUser();
    $reportRows = $reporter->fetchReport($dates['startDate'], $dates['endDate']);
    $reportSummary = \App\Support\ReportSummary::fromTotalledReport($reportRows, $canViewRevenue);
    $reportColumns = ['idrep' => 'Rep ID', 'user_name' => 'Rep', 'Clicks' => 'Raw', 'UniqueClicks' => 'Unique', 'Conversions' => 'Sales'];
    if ($canViewRevenue) $reportColumns['Revenue'] = 'Pay';
    if ($canViewRevenue) $reportColumns += ['TOTAL' => 'Total'];
@endphp
@extends('report.template')
@section('report-title', 'Affiliate Reports')
@section('report-description', $canViewRevenue ? 'Track raw clicks, uniques, conversions, and revenue by rep' : 'Track raw clicks, uniques, and conversions by rep')
@section('report-summary')
    @include('report.partials.summary')
@endsection
@section('table-options')
    @include('report.options.user-type')
    @include('report.options.dates')
@endsection
@section('report-actions')
    @if($userType === Privilege::ROLE_GOD || $userType === Privilege::ROLE_ADMIN)
        <a class="rl-button rl-report-export" href="/report/aff-data/export?{{ http_build_query(array_merge(request()->only(['adminLogin']), ['role' => request('role', 3), 'd_from' => $startDate, 'd_to' => $endDate, 'dateSelect' => $dateSelect])) }}"><i class="fas fa-download" aria-hidden="true"></i> Export Data</a>
    @endif
    <span class="rl-report-count">{{ number_format($reportSummary['count']) }} {{ $reportSummary['count'] === 1 ? 'rep' : 'reps' }}</span>
@endsection
@section('table')
    @include('report.partials.performance-table', ['reportCaption' => 'Affiliate performance'])
@endsection
@section('footer')
    <script>
        $(function () { $('#mainTable').tablesorter({sortList: [[4, 1]], widgets: ['staticRow']}); });
    </script>
@endsection
