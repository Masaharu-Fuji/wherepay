<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * m_ / t_ を外し、Eloquent の既定テーブル名に合わせる.
     * 索引名はテーブル改名では変わらないので、ここで付け直す.
     */
    public function up(): void
    {
        Schema::rename('m_item_categories', 'item_categories');
        Schema::rename('t_rooms', 'rooms');
        Schema::rename('t_members', 'members');
        Schema::rename('t_items', 'items');
        Schema::rename('t_locations', 'locations');
        Schema::rename('t_item_participants', 'item_participants');
        Schema::rename('t_settlements', 'settlements');
        Schema::rename('t_maps', 'maps');

        Schema::table('item_categories', function (Blueprint $table) {
            $table->renameIndex(
                'm_item_categories_category_name_unique',
                'item_categories_category_name_unique',
            );
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->renameIndex(
                't_settlements_room_id_version_index',
                'settlements_room_id_version_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            $table->renameIndex(
                'item_categories_category_name_unique',
                'm_item_categories_category_name_unique',
            );
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->renameIndex(
                'settlements_room_id_version_index',
                't_settlements_room_id_version_index',
            );
        });

        Schema::rename('item_categories', 'm_item_categories');
        Schema::rename('rooms', 't_rooms');
        Schema::rename('members', 't_members');
        Schema::rename('items', 't_items');
        Schema::rename('locations', 't_locations');
        Schema::rename('item_participants', 't_item_participants');
        Schema::rename('settlements', 't_settlements');
        Schema::rename('maps', 't_maps');
    }
};
