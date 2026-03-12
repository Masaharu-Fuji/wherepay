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
        Schema::create('t_maps', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->unsignedBigInteger('room_id');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->foreign('room_id')
                ->references('id')
                ->on('t_rooms')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_maps', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });

        Schema::dropIfExists('t_maps');
    }
};
