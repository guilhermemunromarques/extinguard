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
        Schema::create('extinguishers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('placement_id')->nullable()->unique()->constrained('placements')->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->foreignId('extinguisher_type_id')->constrained('extinguisher_types')->restrictOnDelete();
            $table->foreignId('extinguisher_brand_id')->nullable()->constrained('extinguisher_brands')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('extinguisher_suppliers')->restrictOnDelete();
            $table->decimal('capacity', 5, 2);
            $table->enum('capacity_unit', ['L', 'kg']);
            $table->string('extinguishing_capacity')->nullable();
            $table->string('maintenance_seal')->nullable()->unique();
            $table->string('status')->default('active');
            $table->date('next_refill_date');
            $table->year('next_maintenance_year');
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extinguishers');
    }
};
