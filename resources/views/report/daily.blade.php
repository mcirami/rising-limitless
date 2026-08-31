@extends('report.template')

@php
    $canViewPayouts = \App\Support\PayoutVisibility::forCurrentUser();
@endphp

@section('report-title')
    Daily Reports
@endsection


@section('table-options')
    @include('report.options.dates')
@endsection

@section('table')
    <table class="table table-bordered table-striped table_01 tablesorter" id="mainTable">
        <thead>
        <tr>
            <th class="value_span9">Date</th>
            <th class="value_span9">Raw</th>
            <th class="value_span9">Unique</th>
            <th class="value_span9">Conversions</th>
            @if($canViewPayouts)
                <th class="value_span9">Revenue</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($report as $row)
            <tr>
                <td>{{$row['aggregate_date']}}</td>
                <td>{{$row['clicks']}}</td>
                <td>{{$row['unique_clicks']}}</td>
                <td>{{$row['conversions']}}</td>
                @if($canViewPayouts)
                    <td>{{$row['revenue']}}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
