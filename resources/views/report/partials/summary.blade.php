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
        <span class="rl-metric-label"><i class="fas {{ $canViewRevenue ? 'fa-dollar-sign' : 'fa-hourglass-half' }}" aria-hidden="true"></i> {{ $canViewRevenue ? 'Total Revenue' : 'Pending Conversions' }}</span>
        <strong>@if($canViewRevenue)${{ number_format($reportSummary['Revenue'], 2) }}@else{{ number_format($reportSummary['PendingConversions']) }}@endif</strong>
        <small>{{ $canViewRevenue ? 'Sales revenue' : 'Awaiting completion' }}</small>
    </div>
</div>
