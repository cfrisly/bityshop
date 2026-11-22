<?php

namespace App\Support;

use Konekt\Gears\Defaults\SimpleSetting;
use Konekt\Gears\UI\TreeBuilder;

/**
 * Ajustes de la tienda editables en Admin → Configuración → pestaña "Tienda".
 * Se leen con setting('tienda.xxx').
 */
class Ajustes
{
    public const WHATSAPP = 'tienda.whatsapp_numero';
    public const REGALO_ACTIVO = 'tienda.regalo_activo';
    public const REGALO_COSTO = 'tienda.regalo_costo';
    public const GARANTIA = 'tienda.garantia_texto';
    public const POLITICAS = 'tienda.politicas_texto';
    public const IDIOMA = 'tienda.idioma';
    public const IDIOMA_PREDETERMINADO = 'es';

    public static function registrar($registry): void
    {
        $registry->add(new SimpleSetting(self::WHATSAPP, ''));
        $registry->add(new SimpleSetting(self::REGALO_ACTIVO, true));
        $registry->add(new SimpleSetting(self::REGALO_COSTO, '0'));
        $registry->add(new SimpleSetting(self::GARANTIA, 'Verifica las políticas de garantía de la tienda'));
        $registry->add(new SimpleSetting(self::IDIOMA, self::IDIOMA_PREDETERMINADO, fn () => self::idiomasDisponibles()));
        $registry->add(new SimpleSetting(self::POLITICAS, 'Todos los productos son originales. Si tu producto llega con defectos de fábrica, escríbenos dentro de los primeros 7 días para cambiarlo.'));
    }

    public static function pantallaAdmin(TreeBuilder $ui): void
    {
        $ui->addRootNode('tienda', 'Tienda')
            ->addChildNode('tienda', 'tienda_idioma', 'Idioma / Language')
            ->addSettingItem('tienda_idioma', ['select', ['label' => 'Idioma del sistema (tienda y administración) / System language']], self::IDIOMA)
            ->addChildNode('tienda', 'tienda_regalo', 'Empaque para regalo')
            ->addSettingItem('tienda_regalo', ['checkbox', ['label' => 'Ofrecer empaque para regalo']], self::REGALO_ACTIVO)
            ->addSettingItem('tienda_regalo', ['text', ['label' => 'Costo del empaque (Q, 0 = gratis)']], self::REGALO_COSTO)
            ->addChildNode('tienda', 'tienda_contacto', 'Contacto')
            ->addSettingItem('tienda_contacto', ['text', ['label' => 'Número de WhatsApp con código de país (ej. 50255551234). Vacío = no se muestra']], self::WHATSAPP)
            ->addChildNode('tienda', 'tienda_garantia', 'Garantía')
            ->addSettingItem('tienda_garantia', ['text', ['label' => 'Texto corto de garantía (página del producto)']], self::GARANTIA)
            ->addSettingItem('tienda_garantia', ['text', ['label' => 'Políticas de garantía (se muestran al hacer clic)']], self::POLITICAS);
    }

    /**
     * Idiomas que se pueden elegir: inglés (el original del sistema) y cada
     * archivo de traducción que exista en resources/lang (es.json, fr.json...).
     * Para agregar otro idioma basta con crear su archivo, por ejemplo fr.json.
     */
    public static function idiomasDisponibles(): array
    {
        $codigos = collect(glob(lang_path('*.json')) ?: [])
            ->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))
            ->push('en')
            ->unique()
            ->sort()
            ->values();

        return $codigos->mapWithKeys(fn ($c) => [$c => self::nombreIdioma($c)])->all();
    }

    /** Idioma configurado (español si no hay ninguno o si ya no existe su archivo) */
    public static function idioma(): string
    {
        try {
            $idioma = (string) setting(self::IDIOMA);
        } catch (\Throwable $e) {
            $idioma = '';
        }

        return array_key_exists($idioma, self::idiomasDisponibles()) ? $idioma : self::IDIOMA_PREDETERMINADO;
    }

    /** Idioma guardado, leído directo de la base (sirve antes de que carguen los ajustes) */
    public static function idiomaGuardado(): string
    {
        try {
            $valor = \Illuminate\Support\Facades\DB::table('settings')->where('id', self::IDIOMA)->value('value');
        } catch (\Throwable $e) {
            $valor = null;
        }
        $valor = trim((string) $valor, '"');

        return array_key_exists($valor, self::idiomasDisponibles()) ? $valor : self::IDIOMA_PREDETERMINADO;
    }

    private static function nombreIdioma(string $codigo): string
    {
        $nombres = ['es' => 'Español', 'en' => 'English'];
        if (isset($nombres[$codigo])) {
            return $nombres[$codigo];
        }

        // Nombre del idioma en su propio idioma (ej. fr → Français)
        return class_exists(\Locale::class)
            ? mb_convert_case(\Locale::getDisplayLanguage($codigo, $codigo), MB_CASE_TITLE)
            : strtoupper($codigo);
    }

    public static function whatsapp(): ?string
    {
        $numero = preg_replace('/\D+/', '', (string) setting(self::WHATSAPP));

        return $numero !== '' ? $numero : null;
    }

    /** Costo del empaque para regalo, o null si la opción está desactivada */
    public static function regaloCosto(): ?float
    {
        if (!filter_var(setting(self::REGALO_ACTIVO), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        return max(0.0, (float) str_replace(',', '.', (string) setting(self::REGALO_COSTO)));
    }
}
