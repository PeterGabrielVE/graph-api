<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App;
use Carbon\Carbon;

class LocaleTimezoneMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Header preferido X-Lang, fallback Accept-Language
        $lang = $request->header('X-Lang') ?: $request->header('Accept-Language');
        if ($lang) {
            // tomar solo el primer código (p. ej. "es", "en")
            $langCode = substr($lang, 0, 2);
            try {
                App::setLocale($langCode);
            } catch (\Throwable $e) {
                // si falla, ignorar y mantener default
            }
        }

        // Zona horaria header: X-Timezone (IANA) e.g. "America/Argentina/Buenos_Aires"
        $tz = $request->header('X-Timezone');
        if ($tz) {
            // No cambiar configuración global de la app, sólo disponer en la request
            $request->attributes->set('tz', $tz);
        } else {
            $request->attributes->set('tz', config('app.timezone') ?: 'UTC');
        }

        return $next($request);
    }
}
