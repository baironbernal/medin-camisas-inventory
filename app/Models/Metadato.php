<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadato extends Model
{
    protected $fillable = ['clave', 'valor'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }

    public static function set(string $clave, mixed $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }

    /**
     * Información de la empresa (datos + redes sociales) para la factura y
     * el frontend. Combina los valores por defecto de config/company.php con
     * lo que esté guardado en la tabla `metadatos`.
     *
     * @return array<string, string>
     */
    public static function siteInfo(): array
    {
        $defaults = config('company', []);
        $stored = static::whereIn('clave', array_keys($defaults))
            ->pluck('valor', 'clave');

        $result = [];
        foreach ($defaults as $clave => $default) {
            $valor = $stored[$clave] ?? null;
            $result[$clave] = ($valor === null || $valor === '') ? $default : $valor;
        }

        return $result;
    }
}
