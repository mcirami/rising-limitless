<div class="rl-metrics rl-report-metrics" aria-label="Report summary">
    <div class="rl-metric">
        <span class="rl-metric-label"><i class="fas fa-chart-line" aria-hidden="true"></i> Total Raw Clicks</span>
        <strong>{{ number_format($reportSummary['Clicks']) }}</strong>
        <small>{{ $dates['originalStart'] ?? request('d_from', \Carbon\Carbon::today('America/New_York')->format('Y-m-d')) }}
            @if(($dates['originalEnd'] ?? request('d_to')) && ($dates['originalEnd'] ?? request('d_to')) !== ($dates['originalStart'] ?? request('d_from')))
                – {{ $dates['originalEnd'] ?? request('d_to') }}
            @endif
        </small>
    </div>
    <div class="rl-metric">
        <span class="rl-metric-label"><i class="far fa-user" aria-hidden="true"></i> Unique Visitors</span>
        <strong>{{ number_format($reportSummary['UniqueClicks']) }}</strong>
        <small>{{ number_format($reportSummary['uniqueRate'], 1) }}% unique rate</small>
    </div>
    <div class="rl-metric is-green">
        <span class="rl-metric-label"><i class="fas fa-check" aria-hidden="true"></i> Conversions</span>
        <strong>{{ number_format($reportSummary['Conversions']) }}</strong>
        <small>@if($canViewRevenue) EPC ${{ number_format($reportSummary['EPC'], 2) }} @else Completed conversions @endif</small>
    </div>
    <div class="rl-metric {{ $canViewRevenue ? 'is-green' : '' }}">
        @if($canViewRevenue)
            <span class="rl-metric-label"><i class="fas fa-dollar-sign" aria-hidden="true"></i> Total Revenue</span>
            <strong>${{ number_format($reportSummary['Revenue'], 2) }}</strong>
            <small>Sales revenue</small>
        @elseif(isset($yesterdayConversions))
            <span class="rl-metric-label"><i class="fas fa-calendar-check" aria-hidden="true"></i> Yesterday's Conversions</span>
            <strong>{{ number_format($yesterdayConversions) }}</strong>
            <small>{{ $yesterdayDate }}</small>
        @else
            <span class="rl-metric-label"><i class="fas fa-hourglass-half" aria-hidden="true"></i> Pending Conversions</span>
            <strong>{{ number_format($reportSummary['PendingConversions']) }}</strong>
            <small>Awaiting completion</small>
        @endif
    </div>
</div>
