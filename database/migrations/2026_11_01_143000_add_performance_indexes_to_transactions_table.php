<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexes = [
            'idx_transactions_date' => 'CREATE INDEX idx_transactions_date ON transactions(`date`)',
            'idx_transactions_type_date' => 'CREATE INDEX idx_transactions_type_date ON transactions(`type`, `date`)',
            'idx_transactions_gateway' => 'CREATE INDEX idx_transactions_gateway ON transactions(`payment_gateway`)',
            'idx_transactions_user_id' => 'CREATE INDEX idx_transactions_user_id ON transactions(`user_id`)',
        ];

        foreach ($indexes as $name => $sql) {
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                // Index already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_date');
            $table->dropIndex('idx_transactions_type_date');
            $table->dropIndex('idx_transactions_gateway');
            $table->dropIndex('idx_transactions_user_id');
        });
    }
};
