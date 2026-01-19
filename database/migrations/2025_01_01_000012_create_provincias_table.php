<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bronze.dim_provincias', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('departamento_id');
            $table->string('nombre', 120);
            $table->timestamps();

            $table->foreign('departamento_id')
                ->references('id')
                ->on('bronze.dim_departamentos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_provincias');
    }
};
