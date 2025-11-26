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
        Schema::table('players', function (Blueprint $table) {
            // Add position fields
            $table->string('primary_position')->nullable()->after('position');
            $table->string('secondary_position')->nullable()->after('primary_position');
            $table->string('third_position')->nullable()->after('secondary_position');
            
            // Profile picture URL
            $table->string('profile_picture')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['primary_position', 'secondary_position', 'third_position']);
        });
    }
};
