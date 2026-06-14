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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('property_name');
            $table->date('check_in');
            $table->date('check_out');
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índice compuesto para los filtros por rango de fechas
            $table->index(['check_in', 'check_out']);
        });
    }

    /*
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
