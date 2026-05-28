<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Catálogo de países con código ISO-2, zona horaria principal y bandera.
 *
 * Se usa para autocompletar el formulario de "Crear país" en el portal.
 * La lista sale del intl (ResourceBundle) en runtime para cubrir los ~250
 * países sin tener que mantener un dataset hardcodeado.
 */
class CountryCatalog
{
    /**
     * Países con múltiples zonas: elegimos la "ciudad capital / más usada"
     * como default. El admin puede cambiarla en el form si necesita.
     */
    private const TZ_OVERRIDES = [
        'US' => 'America/New_York',
        'CA' => 'America/Toronto',
        'MX' => 'America/Mexico_City',
        'BR' => 'America/Sao_Paulo',
        'AR' => 'America/Argentina/Buenos_Aires',
        'CL' => 'America/Santiago',
        'EC' => 'America/Guayaquil',
        'RU' => 'Europe/Moscow',
        'CN' => 'Asia/Shanghai',
        'AU' => 'Australia/Sydney',
        'ID' => 'Asia/Jakarta',
        'KZ' => 'Asia/Almaty',
        'MN' => 'Asia/Ulaanbaatar',
        'CD' => 'Africa/Kinshasa',
        'GL' => 'America/Nuuk',
        'KI' => 'Pacific/Tarawa',
        'FM' => 'Pacific/Pohnpei',
        'PF' => 'Pacific/Tahiti',
        'PT' => 'Europe/Lisbon',
        'ES' => 'Europe/Madrid',
        'NZ' => 'Pacific/Auckland',
    ];

    /**
     * @return list<array{name:string,code:string,timezone:string,flag_emoji:string}>
     */
    public static function all(): array
    {
        return Cache::rememberForever('app.country_catalog', fn() => self::build());
    }

    /** Busca una entrada por nombre exacto. */
    public static function findByName(string $name): ?array
    {
        foreach (self::all() as $entry) {
            if ($entry['name'] === $name) {
                return $entry;
            }
        }
        return null;
    }

    private static function build(): array
    {
        $countries = [];

        if (class_exists(\ResourceBundle::class)) {
            try {
                $bundle = \ResourceBundle::create('en', 'ICUDATA-region');
                $names  = $bundle?->get('Countries');

                if ($names instanceof \ResourceBundle) {
                    foreach ($names as $code => $name) {
                        if (! is_string($code) || strlen($code) !== 2 || ! ctype_upper($code)) {
                            continue;
                        }

                        $tzs = @\DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $code);
                        if (empty($tzs)) {
                            continue;
                        }

                        $countries[] = [
                            'name'       => (string) $name,
                            'code'       => $code,
                            'timezone'   => self::TZ_OVERRIDES[$code] ?? $tzs[0],
                            'flag_emoji' => self::flag($code),
                        ];
                    }
                }
            } catch (\Throwable) {
                // Si algo falla con intl, caemos al fallback abajo.
            }
        }

        if (empty($countries)) {
            $countries = self::fallback();
        }

        usort($countries, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $countries;
    }

    private static function flag(string $code): string
    {
        $code = strtoupper($code);
        if (strlen($code) !== 2) {
            return '';
        }
        $a = mb_ord($code[0]) - 0x41 + 0x1F1E6;
        $b = mb_ord($code[1]) - 0x41 + 0x1F1E6;
        return mb_chr($a) . mb_chr($b);
    }

    /** Mínimo fallback si intl no estuviera disponible (raro en Laravel). */
    private static function fallback(): array
    {
        return [
            ['name' => 'Argentina',          'code' => 'AR', 'timezone' => 'America/Argentina/Buenos_Aires', 'flag_emoji' => '🇦🇷'],
            ['name' => 'Belize',             'code' => 'BZ', 'timezone' => 'America/Belize',                 'flag_emoji' => '🇧🇿'],
            ['name' => 'Bolivia',            'code' => 'BO', 'timezone' => 'America/La_Paz',                 'flag_emoji' => '🇧🇴'],
            ['name' => 'Brazil',             'code' => 'BR', 'timezone' => 'America/Sao_Paulo',              'flag_emoji' => '🇧🇷'],
            ['name' => 'Canada',             'code' => 'CA', 'timezone' => 'America/Toronto',                'flag_emoji' => '🇨🇦'],
            ['name' => 'Chile',              'code' => 'CL', 'timezone' => 'America/Santiago',               'flag_emoji' => '🇨🇱'],
            ['name' => 'Colombia',           'code' => 'CO', 'timezone' => 'America/Bogota',                 'flag_emoji' => '🇨🇴'],
            ['name' => 'Costa Rica',         'code' => 'CR', 'timezone' => 'America/Costa_Rica',             'flag_emoji' => '🇨🇷'],
            ['name' => 'Cuba',               'code' => 'CU', 'timezone' => 'America/Havana',                 'flag_emoji' => '🇨🇺'],
            ['name' => 'Dominican Republic', 'code' => 'DO', 'timezone' => 'America/Santo_Domingo',          'flag_emoji' => '🇩🇴'],
            ['name' => 'Ecuador',            'code' => 'EC', 'timezone' => 'America/Guayaquil',              'flag_emoji' => '🇪🇨'],
            ['name' => 'El Salvador',        'code' => 'SV', 'timezone' => 'America/El_Salvador',            'flag_emoji' => '🇸🇻'],
            ['name' => 'France',             'code' => 'FR', 'timezone' => 'Europe/Paris',                   'flag_emoji' => '🇫🇷'],
            ['name' => 'Germany',            'code' => 'DE', 'timezone' => 'Europe/Berlin',                  'flag_emoji' => '🇩🇪'],
            ['name' => 'Guatemala',          'code' => 'GT', 'timezone' => 'America/Guatemala',              'flag_emoji' => '🇬🇹'],
            ['name' => 'Honduras',           'code' => 'HN', 'timezone' => 'America/Tegucigalpa',            'flag_emoji' => '🇭🇳'],
            ['name' => 'Italy',              'code' => 'IT', 'timezone' => 'Europe/Rome',                   'flag_emoji' => '🇮🇹'],
            ['name' => 'Japan',              'code' => 'JP', 'timezone' => 'Asia/Tokyo',                     'flag_emoji' => '🇯🇵'],
            ['name' => 'Mexico',             'code' => 'MX', 'timezone' => 'America/Mexico_City',            'flag_emoji' => '🇲🇽'],
            ['name' => 'Nicaragua',          'code' => 'NI', 'timezone' => 'America/Managua',                'flag_emoji' => '🇳🇮'],
            ['name' => 'Panama',             'code' => 'PA', 'timezone' => 'America/Panama',                 'flag_emoji' => '🇵🇦'],
            ['name' => 'Paraguay',           'code' => 'PY', 'timezone' => 'America/Asuncion',               'flag_emoji' => '🇵🇾'],
            ['name' => 'Peru',               'code' => 'PE', 'timezone' => 'America/Lima',                   'flag_emoji' => '🇵🇪'],
            ['name' => 'Portugal',           'code' => 'PT', 'timezone' => 'Europe/Lisbon',                  'flag_emoji' => '🇵🇹'],
            ['name' => 'Spain',              'code' => 'ES', 'timezone' => 'Europe/Madrid',                  'flag_emoji' => '🇪🇸'],
            ['name' => 'United Kingdom',     'code' => 'GB', 'timezone' => 'Europe/London',                  'flag_emoji' => '🇬🇧'],
            ['name' => 'United States',      'code' => 'US', 'timezone' => 'America/New_York',               'flag_emoji' => '🇺🇸'],
            ['name' => 'Uruguay',            'code' => 'UY', 'timezone' => 'America/Montevideo',             'flag_emoji' => '🇺🇾'],
            ['name' => 'Venezuela',          'code' => 'VE', 'timezone' => 'America/Caracas',                'flag_emoji' => '🇻🇪'],
        ];
    }
}
