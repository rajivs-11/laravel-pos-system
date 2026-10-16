<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use Exception;

class LoyaltyService {
    public function awardPoints(int $customerId, int $points, int $invoiceId): void {
        $customer = Customer::lockForUpdate()->findOrFail($customerId);
        $customer->loyalty_points += $points;
        $customer->save();

        LoyaltyTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'earned',
            'points' => $points,
            'invoice_id' => $invoiceId,
            'notes' => "Earned on invoice #{$invoiceId}",
        ]);
    }

    public function redeemPoints(int $customerId, int $points, int $invoiceId): void {
        $customer = Customer::lockForUpdate()->findOrFail($customerId);
        if ($customer->loyalty_points < $points) {
            throw new Exception("Insufficient loyalty points. Available: {$customer->loyalty_points}");
        }

        $customer->loyalty_points -= $points;
        $customer->save();

        LoyaltyTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'redeemed',
            'points' => $points,
            'invoice_id' => $invoiceId,
            'notes' => "Redeemed on invoice #{$invoiceId}",
        ]);
    }
}
