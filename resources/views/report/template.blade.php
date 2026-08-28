@extends('layouts.master')
@section('content')
    <div class="right_panel rl-report">
        <div class="white_box_outer large_table">
            <div class="rl-page-heading"><div><h1>@yield('report-title')</h1><p>@yield('report-description', 'Explore your network performance and reporting data')</p></div></div>
            @yield('report-summary')
            <section class="rl-card rl-report-panel" aria-label="Report results">
            <div class="rl-report-controls">
                <div class="form-group">@yield('table-options')</div>
                <div class="form-group filter_form">@yield('filters')</div>
            </div>

            @hasSection('report-actions')
                <div class="rl-report-actions">@yield('report-actions')</div>
            @endif
            <div class="rl-report-table-wrap" role="region" aria-label="Scrollable report table" tabindex="0">
                @yield('table')
            </div>
            </section>

            @yield('extra')

        </div>
    </div>
    <script src="{{ $webroot }}js/network-reports.js?v={{ filemtime(public_path('js/network-reports.js')) }}" defer></script>
@endsection
