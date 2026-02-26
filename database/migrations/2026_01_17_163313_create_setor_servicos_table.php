<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setor_servicos', function (Blueprint $table) {
            $table->id();
            $table->string('setor');
            $table->string('nivel');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setor_servicos');
    }
};
