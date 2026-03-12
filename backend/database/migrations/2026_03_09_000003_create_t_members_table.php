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
        Schema::create('t_members', function (Blueprint $table) {
            $table->id();
            $table->string('member_name')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->timestamp('created_at');
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
        Schema::table('t_members', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });

        Schema::dropIfExists('t_members');
    }
};
