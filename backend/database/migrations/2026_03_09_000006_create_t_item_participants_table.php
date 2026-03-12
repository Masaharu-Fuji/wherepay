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
        Schema::create('t_item_participants', function (Blueprint $table) {
            $table->id();
            $table->integer('share_amount');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('member_id');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->foreign('item_id')
                ->references('id')
                ->on('t_items')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('member_id')
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
        Schema::table('t_item_participants', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['member_id']);
        });

        Schema::dropIfExists('t_item_participants');
    }
};
