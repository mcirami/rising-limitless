@php
    $canViewRevenue = true; // Agents already see their own earnings on this report.
    $reportRows = $reporter->fetchReport($dates['startDate'], $dates['endDate']);
    $reportSummary = \App\Support\ReportSummary::fromTotalledReport($reportRows, $canViewRevenue);
    $reportColumns = ['idoffer' => 'Offer ID', 'offer_name' => 'Offer Name', 'Clicks' => 'Raw', 'UniqueClicks' => 'Unique', 'FreeSignUps' => 'Free Sign Ups', 'PendingConversions' => 'Pending Conversions', 'Conversions' => 'Conversions', 'Revenue' => 'Revenue', 'Deductions' => 'Deductions', 'EPC' => 'EPC', 'TOTAL' => 'Total'];
@endphp
@extends('report.template')

@section('report-title')
    Click Reports
@endsection

@section('report-description', 'Track clicks, conversions, and earnings across your offers')
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
    @if($report->bonuses)
        <table class="table table-bordered table_01">
            <thead>
            <tr>
                <td>Bonus Name</td>
                <td>Bonus Revenue</td>
            </tr>
            </thead>
            <tbody>
            @php($report->printBonuses())
            </tbody>
        </table>
    @endif
@endsection



@section('footer')
    <script type="text/javascript">

        $(document).ready(function () {
            $('#mainTable').tablesorter(
                {
                    sortList: [[6, 1]],
                    widgets: ['staticRow'],
                });
        });
    </script>
@endsection
