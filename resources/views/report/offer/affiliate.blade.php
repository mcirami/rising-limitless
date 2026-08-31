@php
    $canViewRevenue = false;
    $reportRows = $reporter->fetchReport($dates['startDate'], $dates['endDate']);
    $reportSummary = \App\Support\ReportSummary::fromTotalledReport($reportRows, $canViewRevenue);
    $reportColumns = ['idoffer' => 'Offer ID', 'offer_name' => 'Offer Name', 'Clicks' => 'Raw', 'UniqueClicks' => 'Unique', 'Conversions' => 'Conversions'];
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
    @include('report.partials.performance-table', ['reportCaption' => 'Your offer performance'])
@endsection



@section('footer')
    <script type="text/javascript">

        $(document).ready(function () {
            $('#mainTable').tablesorter(
                {
                    sortList: [[4, 1]],
                    widgets: ['staticRow'],
                });
        });
    </script>
@endsection
