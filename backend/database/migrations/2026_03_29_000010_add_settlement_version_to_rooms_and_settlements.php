<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_rooms', function (Blueprint $table) {
            $table->unsignedInteger('settlement_version')->default(0);
        });

        Schema::table('t_settlements', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
        });

        DB::table('t_settlements')->update(['version' => 1]);

        $roomIds = DB::table('t_settlements')->distinct()->pluck('room_id');
        foreach ($roomIds as $roomId) {
            DB::table('t_rooms')->where('id', $roomId)->update(['settlement_version' => 1]);
        }

        Schema::table('t_settlements', function (Blueprint $table) {
            $table->index(['room_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_settlements', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'version']);
        });

        Schema::table('t_settlements', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('t_rooms', function (Blueprint $table) {
            $table->dropColumn('settlement_version');
        });
    }
};
