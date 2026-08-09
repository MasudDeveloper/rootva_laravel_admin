<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Vendors Table
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // References sign_up.id
            $table->string('store_name');
            $table->text('store_description')->nullable();
            $table->string('status')->default('pending'); // pending, approved, suspended
            $table->decimal('commission_rate', 5, 2)->default(10.00); // 10% commission on sales
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('sign_up')->onDelete('cascade');
        });

        // 2. Create Product Favorites Table
        Schema::create('product_favorites', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // References sign_up.id
            $table->integer('product_id'); // References products.id
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('sign_up')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['user_id', 'product_id']);
        });

        // 3. Update Products Table
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('category_id');
            $table->tinyInteger('is_approved')->default(1)->after('reselling_price'); // 1 = approved (admin uploads), 0 = pending (vendor uploads)
            $table->integer('stock')->default(100)->after('is_approved');

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'is_approved', 'stock']);
        });

        Schema::dropIfExists('product_favorites');
        Schema::dropIfExists('vendors');
    }
};
