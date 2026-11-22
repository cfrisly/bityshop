<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los 22 departamentos de Guatemala. Se usan en las direcciones y para armar
 * las zonas de envío del admin (ej. "Capital" = Guatemala, "Interior" = el resto).
 */
return new class extends Migration
{
    private array $departamentos = [
        'AV' => 'Alta Verapaz',
        'BV' => 'Baja Verapaz',
        'CM' => 'Chimaltenango',
        'CQ' => 'Chiquimula',
        'PR' => 'El Progreso',
        'ES' => 'Escuintla',
        'GU' => 'Guatemala',
        'HU' => 'Huehuetenango',
        'IZ' => 'Izabal',
        'JA' => 'Jalapa',
        'JU' => 'Jutiapa',
        'PE' => 'Petén',
        'QZ' => 'Quetzaltenango',
        'QC' => 'Quiché',
        'RE' => 'Retalhuleu',
        'SA' => 'Sacatepéquez',
        'SM' => 'San Marcos',
        'SR' => 'Santa Rosa',
        'SO' => 'Sololá',
        'SU' => 'Suchitepéquez',
        'TO' => 'Totonicapán',
        'ZA' => 'Zacapa',
    ];

    public function up(): void
    {
        if (!DB::table('countries')->where('id', 'GT')->exists()) {
            return;
        }

        foreach ($this->departamentos as $code => $name) {
            $exists = DB::table('provinces')->where('country_id', 'GT')->where('code', $code)->exists();
            if (!$exists) {
                DB::table('provinces')->insert([
                    'country_id' => 'GT',
                    'type' => 'province',
                    'code' => $code,
                    'name' => $name,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('provinces')
            ->where('country_id', 'GT')
            ->whereIn('code', array_keys($this->departamentos))
            ->delete();
    }
};
