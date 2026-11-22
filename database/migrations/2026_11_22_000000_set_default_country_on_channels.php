<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El listado de canales del panel admin (/admin/channel) lee configuration.country_id
 * y falla con "Undefined array key country_id" si el canal no tiene país.
 * Se asigna el país por defecto de la tienda a los canales que no lo tengan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $country = DB::table('settings')->where('id', 'appshell.default.country')->value('value') ?: 'GT';
        $country = trim((string) $country, '"');

        foreach (DB::table('channels')->get(['id', 'configuration']) as $channel) {
            $config = json_decode((string) $channel->configuration, true) ?: [];
            if (!array_key_exists('country_id', $config)) {
                $config['country_id'] = $country;
                DB::table('channels')->where('id', $channel->id)->update(['configuration' => json_encode($config)]);
            }
        }
    }

    public function down(): void
    {
        // Sin cambios: el país asignado no afecta los datos existentes
    }
};
