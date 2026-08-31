<?php

namespace App\Support;

use App\Privilege;
use LeadMax\TrackYourStats\System\Session;
use LeadMax\TrackYourStats\User\Permissions;

final class PayoutVisibility
{
    public static function forCurrentUser(): bool
    {
        $role = (int) Session::userType();

        if ($role === Privilege::ROLE_GOD) {
            return true;
        }

        return $role === Privilege::ROLE_ADMIN
            && Session::permissions()->can(Permissions::VIEW_PAYOUTS);
    }

    public static function withoutPayoutFields(array $rows): array
    {
        $payoutFields = [
            'Revenue',
            'Deductions',
            'EPC',
            'TOTAL',
            'BonusRevenue',
            'ReferralRevenue',
            'paid',
            'payout',
        ];

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($payoutFields as $field) {
                unset($row[$field]);
            }
        }
        unset($row);

        return $rows;
    }
}
