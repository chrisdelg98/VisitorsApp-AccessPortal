<?php

namespace App\Support;

use App\Models\Country;
use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;

/**
 * Formatea fechas en la zona horaria del país donde ocurrió el evento,
 * con la etiqueta visible y un data-utc para que el JS calcule la hora
 * local del browser en hover (ver renderHook en AdminPanelProvider).
 */
class TzFormatter
{
    /** HTML con span.efl-tz para que el browser del usuario calcule su hora local en hover. */
    public static function forCountry(?CarbonInterface $date, ?Country $country, string $format = 'd/m/Y H:i'): ?HtmlString
    {
        if (! $date) {
            return null;
        }

        [$localStr, $code] = static::resolve($date, $country, $format);
        $utcIso = $date->copy()->utc()->toIso8601String();

        return new HtmlString(
            '<span class="efl-tz" data-utc="' . e($utcIso) . '">'
            . e("{$localStr} {$code}")
            . '</span>'
        );
    }

    /** Versión texto plano (para CSV / payloads JSON donde HTML no aplica). */
    public static function plain(?CarbonInterface $date, ?Country $country, string $format = 'd/m/Y H:i'): ?string
    {
        if (! $date) {
            return null;
        }

        [$localStr, $code] = static::resolve($date, $country, $format);

        return "{$localStr} {$code}";
    }

    /** UTC ISO-8601, conveniencia para mandar a JS y armar el tooltip allá. */
    public static function utcIso(?CarbonInterface $date): ?string
    {
        return $date?->copy()->utc()->toIso8601String();
    }

    /**
     * @return array{0:string,1:string}  [hora formateada, código corto]
     */
    private static function resolve(CarbonInterface $date, ?Country $country, string $format): array
    {
        $tz   = $country?->timezone ?: 'UTC';
        $code = $country?->code ?: 'UTC';

        $localStr = $date->copy()->setTimezone($tz)->format($format);

        return [$localStr, $code];
    }
}
