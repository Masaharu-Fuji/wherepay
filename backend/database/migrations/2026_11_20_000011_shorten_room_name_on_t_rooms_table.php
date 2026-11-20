<?php

use App\Models\Room;
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
        Schema::table('t_rooms', function (Blueprint $table) {
            $table->string('room_name', Room::NAME_MAX_LENGTH)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_rooms', function (Blueprint $table) {
            $table->string('room_name')->change();
        });
    }
};
