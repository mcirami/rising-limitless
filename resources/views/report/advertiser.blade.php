@extends('report.template')

@php
    $canViewPayouts = \App\Support\PayoutVisibility::forCurrentUser();
@endphp

@section('report-title')
    Advertiser Reports
@endsection

@section('table-options')
    @include('report.options.dates')
@endsection

@section('table')
    <table class="table table-bordered table-striped table_01 tablesorter" id="mainTable">
        <thead>
        <tr>
            <th class="value_span9">ID</th>
            <th class="value_span9">Name</th>
            <th class="value_span9">Raw</th>
            <th class="value_span9">Unique</th>
            <th class="value_span9">Conversion</th>
            @if($canViewPayouts)
                <th class="value_span9">Revenue</th>
                <th class="value_span9">EPC</th>
                <th class="value_span9">TOTAL</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @php
            $reporter->between($dates['startDate'],$dates['endDate'], new \LeadMax\TrackYourStats\Report\Formats\HTML(true,
            $canViewPayouts
                ? ['id','name','Clicks','UniqueClicks','Conversions','Revenue','EPC','TOTAL']
                : ['id','name','Clicks','UniqueClicks','Conversions']
            ));
        @endphp
        </tbody>
    </table>
@endsection

@section('footer')
    <script type="text/javascript">
        $(document).ready(function () {
            $("#mainTable").tablesorter(
                {
                    sortList: [[{{ $canViewPayouts ? 5 : 4 }}, 1]],
                    widgets: ['staticRow']
                });
        });
    </script>
@endsection
