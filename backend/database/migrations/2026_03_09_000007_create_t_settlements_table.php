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
        Schema::create('t_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('payer_id');
            $table->unsignedBigInteger('receiver_id');
            $table->integer('amount');
            $table->boolean('is_paid')->default(false);
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->foreign('room_id')
                ->references('id')
                ->on('t_rooms')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('payer_id')
                ->references('id')
                ->on('t_members')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('receiver_id')
                ->references('id')
                ->on('t_members')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_settlements', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropForeign(['payer_id']);
            $table->dropForeign(['receiver_id']);
        });

        Schema::dropIfExists('t_settlements');
    }
};
