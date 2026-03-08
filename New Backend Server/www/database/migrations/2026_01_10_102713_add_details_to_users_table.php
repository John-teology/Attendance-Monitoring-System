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
            $table->string('department')->nullable()->after('rfid_uid');
            $table->string('course')->nullable()->after('department'); // Student only
            $table->string('year_level')->nullable()->after('course'); // Student only
            $table->string('designation')->nullable()->after('year_level'); // Faculty only
            $table->date('id_expiration_date')->nullable()->after('designation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'course',
                'year_level',
                'designation',
                'id_expiration_date',
            ]);
        });
    }
};
