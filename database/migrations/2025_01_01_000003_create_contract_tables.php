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
        // Create contracts table
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->text('title'); // Judul Kontrak/Nama Pekerjaan
            $table->string('contract_number')->nullable(); // No. Kontrak
            $table->enum('currency_type', ['rp', 'non_rp'])->nullable(); // Currency type
            $table->decimal('contract_value_rp', 15, 2)->nullable(); // Nilai Kontrak (Rp)
            $table->decimal('contract_value_non_rp', 15, 2)->nullable(); // Nilai Kontrak (Non Rp)
            $table->decimal('lumpsum_value_rp', 15, 2)->nullable(); // Nilai Kontrak Lumpsum (Rp)
            $table->decimal('unit_value_rp', 15, 2)->nullable(); // Nilai Kontrak Satuan (Rp)
        });

        // Create contract_periods table
        Schema::create('contract_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->string('period_name'); // Nama Periode Pelaksanaan Termin Pembayaran
            $table->enum('budget_type', ['AO', 'RI']); // Anggaran Untuk Realisasi (AO/RI)
            $table->date('start_date')->nullable(); // Periode Tahapan (Awal)
            $table->date('end_date')->nullable(); // Periode Tahapan (Akhir)
            $table->decimal('payment_value_rp', 15, 2)->nullable(); // Nilai Termin Pembayaran Sesuai Kontrak (Rp)
            $table->decimal('payment_value_non_rp', 15, 2)->nullable(); // Nilai Termin Pembayaran Sesuai Kontrak (Non Rp)
            $table->enum('payment_status', [
                'paid', // 1. Sudah bayar
                'ba_process', // 2. Proses BA
                'mka_process', // 3. Proses di MKA
                'settlement_process', // 4. Proses Settlement (LD<=9 Des)
                'addendum_process', // 5. Proses Addendum
                'not_due', // 6. Belum Jatuh Tempo/belum ada kebutuhan
                'has_issue', // 7. Terdapat Isu
                'unpaid', // 8. Tidak bayar
                'reserved_hr', // 9. Dicadangkan (HR)
                'contract_moved' // 10. Kontrak dipindahkan
            ]);
        });

        // Create app_contract pivot table
        Schema::create('app_contract', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            
            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
            $table->unique(['app_id', 'contract_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_contract');
        Schema::dropIfExists('contract_periods');
        Schema::dropIfExists('contracts');
    }
};
