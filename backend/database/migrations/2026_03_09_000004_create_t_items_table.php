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
        Schema::create('t_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->text('memo')->nullable();
            $table->integer('amount');
            $table->date('paid_at');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('payer_id');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->foreign('category_id')
                ->references('id')
                ->on('m_item_categories')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('payer_id')
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
        Schema::table('t_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['payer_id']);
        });

        Schema::dropIfExists('t_items');
    }
};
