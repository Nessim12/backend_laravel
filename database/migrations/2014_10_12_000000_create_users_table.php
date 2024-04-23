<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('cin');
        $table->string('firstname');
        $table->string('lastname');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('tel');
        $table->string('adresse');
        $table->float('soldecongée')->default(20);
        $table->enum('genre', ['women', 'men']);
        $table->string('avatar')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
