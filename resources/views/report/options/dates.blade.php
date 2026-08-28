@php
    $selectedReportRange = (int) request()->query('dateSelect', 0);
    $reportFrom = $dates['originalStart'] ?? request()->query('d_from', \Carbon\Carbon::today('America/New_York')->format('Y-m-d'));
    $reportTo = $dates['originalEnd'] ?? request()->query('d_to', \Carbon\Carbon::today('America/New_York')->format('Y-m-d'));
@endphp
<script>var dateSelect = {{ $selectedReportRange }};</script>
<select onchange="handleDateSelect(this);" class="selectBox" id="preDefined" name="preDefined" aria-label="Report date range">
    @foreach([0 => 'Today', 1 => 'Yesterday', 2 => 'Week to Date', 3 => 'Month to Date', 4 => 'Year to Date', 5 => 'Last Week', 6 => 'Last Month', 7 => 'Custom Range'] as $value => $label)
        <option value="{{ $value }}" @selected($selectedReportRange === $value)>{{ $label }}</option>
    @endforeach
</select>
<span class="rl-report-date">
    <label for="d_from">From:</label>
    <input onchange="setCustom();" type="text" id="d_from" name="d_from" value="{{ $reportFrom }}" autocomplete="off" placeholder="YYYY-MM-DD" aria-label="Report start date" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">
</span>
<span class="rl-report-date">
    <label for="d_to">To:</label>
    <input onchange="setCustom();" type="text" id="d_to" name="d_to" value="{{ $reportTo }}" autocomplete="off" placeholder="YYYY-MM-DD" aria-label="Report end date" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">
</span>
<div class="button_wrap">
    <button type="button" id="searchBtn" class="rl-button rl-primary" onclick="searchReportDates()"><i class="fas fa-search" aria-hidden="true"></i> Search</button>
</div>
@once
<script src="{{ $webroot }}js/network-report-dates.js?v={{ filemtime(public_path('js/network-report-dates.js')) }}" defer></script>
@endonce
