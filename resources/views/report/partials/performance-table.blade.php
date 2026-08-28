<table class="table table_01 tablesorter rl-performance-table" id="mainTable" data-performance-table>
    <caption class="sr-only">{{ $reportCaption }} for the selected date range</caption>
    <thead><tr>
        @foreach($reportColumns as $key => $label)
            <th scope="col" data-report-field="{{ $key }}">{{ $label }}</th>
        @endforeach
    </tr></thead>
    <tbody>
        @if($reportSummary['count'])
            @php
                // Reuse the existing formatter and drill-down URLs with the already fetched rows.
                $detailRows = array_slice($reportRows, 0, -1);
                foreach ($detailRows as &$detailRow) {
                    foreach ($reportColumns as $field => $label) $detailRow[$field] = $detailRow[$field] ?? '';
                }
                unset($detailRow);
                (new \LeadMax\TrackYourStats\Report\Formats\HTML(false, array_keys($reportColumns), $dates))->output($detailRows);
            @endphp
        @else
            <tr class="static rl-report-empty"><td colspan="{{ count($reportColumns) }}"><i class="far fa-chart-bar" aria-hidden="true"></i><strong>No activity in this date range</strong><span>Try another date range to see your report.</span></td></tr>
        @endif
    </tbody>
    @if($reportSummary['count'])
        <tfoot><tr class="static">
            @foreach($reportColumns as $key => $label)
                <td>
                    @if($loop->first)
                        —
                    @elseif($loop->index === 1)
                        TOTALS
                    @else
                        {{ strip_tags((string) (end($reportRows)[$key] ?? '')) }}
                    @endif
                </td>
            @endforeach
        </tr></tfoot>
    @endif
</table>
