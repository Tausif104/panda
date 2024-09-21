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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->index()->after('email');
            $table->string('phone_number')->nullable()->after('city_id');
            $table->string('avatar_dir')->nullable()->after('phone_number');
            $table->string('gender')->nullable()->after('avatar_dir');
            $table->foreignId('specialty_id')->nullable()->after('gender')->index();
            $table->boolean('active')->default(true)->after('specialty_id');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
