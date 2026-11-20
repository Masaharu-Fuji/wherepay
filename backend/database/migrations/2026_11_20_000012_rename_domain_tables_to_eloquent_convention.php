<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * m_ / t_ を外し、Eloquent の既定テーブル名に合わせる.
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
