<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_accesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes');
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->foreignId('otorgado_por')->constrained('users');
            $table->timestamp('otorgado_en');
            $table->foreignId('revocado_por')->nullable()->constrained('users');
            $table->timestamp('revocado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_accesos');
    }
};
