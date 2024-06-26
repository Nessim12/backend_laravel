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
        Schema::create('demande_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date_d');
            $table->date('date_f');
            $table->foreignId('motif_id')->constrained('motifs')->onDelete('cascade');
            $table->string('description');
            $table->enum('status', ['en_cours', 'accepter', 'refuser'])->default('en_cours');
            $table->string('refuse_reason')->nullable();
            $table->float('solde');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_conge');
    }
};
