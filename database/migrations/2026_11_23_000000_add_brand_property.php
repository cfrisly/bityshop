<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Propiedad "Marca" para los productos. Las marcas (Timberland, Dell, Samsung...)
 * se agregan en Admin → Propiedades → Marca y se asignan en cada producto.
 * La página del producto muestra "Visita la tienda de <marca>".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('properties')->where('slug', 'marca')->exists()) {
            return;
        }

        DB::table('properties')->insert([
            'name' => 'Marca',
            'slug' => 'marca',
            'type' => 'text',
            'is_hidden' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $id = DB::table('properties')->where('slug', 'marca')->value('id');
        if ($id && !DB::table('property_values')->where('property_id', $id)->exists()) {
            DB::table('properties')->where('id', $id)->delete();
        }
    }
};
