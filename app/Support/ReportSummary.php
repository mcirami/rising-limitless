<?php

namespace App\Support;

final class ReportSummary
{
    /** Read the existing Total filter's final row; never query or total it a second time. */
    public static function fromTotalledReport(array $rows, bool $canViewRevenue): array
    {
        $totals = $rows ? end($rows) : [];
        $summary = ['count' => max(0, count($rows) - 1)];
        foreach (['Clicks', 'UniqueClicks', 'Conversions', 'PendingConversions'] as $key) {
            $summary[$key] = self::number($totals[$key] ?? 0);
        }
        $summary['uniqueRate'] = $summary['Clicks'] > 0 ? $summary['UniqueClicks'] / $summary['Clicks'] * 100 : 0.0;
        // Do not pass restricted financial values into the presentation layer.
        if ($canViewRevenue) {
            $summary['Revenue'] = self::number($totals['Revenue'] ?? 0);
            $summary['EPC'] = self::number($totals['EPC'] ?? 0);
        }
        return $summary;
    }

    private static function number($value): float
    {
        return (float) str_replace([',', '$'], '', trim(strip_tags((string) $value)));
    }
}
