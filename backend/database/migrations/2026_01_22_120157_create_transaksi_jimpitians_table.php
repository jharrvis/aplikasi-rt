<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_jimpitians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('warga_id')->constrained('wargas');
            $table->foreignId('pelapor_id')->nullable()->constrained('wargas');
            $table->decimal('nominal', 10, 2);
            $table->text('keterangan')->nullable();
            $table->string('status')->default('verified');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_jimpitians');
    }
};
