<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvoiceNumberService {
    public function generateNextInvoiceNumber(): string {
        return DB::transaction(function () {
            $sequence = DB::table('invoice_sequences')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                DB::table('invoice_sequences')->insert([
                    'id' => 1,
                    'prefix' => 'GUT-',
                    'current_value' => 1001,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 'GUT-001001';
            }

            $nextVal = $sequence->current_value + 1;

            DB::table('invoice_sequences')
                ->where('id', 1)
                ->update([
                    'current_value' => $nextVal,
                    'updated_at' => now(),
                ]);

            return $sequence->prefix . str_pad((string)$nextVal, 6, '0', STR_PAD_LEFT);
        });
    }
}
