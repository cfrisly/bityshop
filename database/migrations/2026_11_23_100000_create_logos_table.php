<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Konekt\Acl\Models\PermissionProxy;
use Konekt\Acl\Models\RoleProxy;
use Konekt\Acl\PermissionRegistrar;
use Konekt\AppShell\Acl\ResourcePermissionMapper;

/**
 * Logos de la tienda (Admin → Configuración → Logos de temporada).
 * Un logo principal y logos de temporada (Navidad, Halloween...) que se
 * muestran solos entre sus fechas de inicio y fin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('imagen');
            $table->boolean('es_principal')->default(false);
            $table->date('desde')->nullable();
            $table->date('hasta')->nullable();
            $table->boolean('cada_anio')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Permisos del admin: list/create/view/edit/delete logos
        $permissions = (new ResourcePermissionMapper())->allPermissionsFor('logo');
        foreach ($permissions as $permission) {
            if (!PermissionProxy::where('name', $permission)->exists()) {
                PermissionProxy::create(['name' => $permission]);
            }
        }
        if ($admin = RoleProxy::where('name', 'admin')->first()) {
            $admin->givePermissionTo(...$permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ((new ResourcePermissionMapper())->allPermissionsFor('logo') as $permission) {
            PermissionProxy::where('name', $permission)->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('logos');
    }
};
