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
        Schema::create('buys', function (Blueprint $table) {
            $table->id();
            $table->string('comprobante',255);
            $table->foreignId('delivery_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('client',200)->nullable();
            $table->enum('type',['delivery','restaurant']);
            //-1 anulado: 0 pendiente : 1 asignado/en camino : 2 entregado
            $table->enum('status',[-1,0,1,2])->default(0); 
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buys');
    }
};
