<?php

declare(strict_types=1);

namespace AstralOrm\Model\Traits;

use AstralOrm\Exceptions\CastException;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Trait HasCasts
 *
 * Conversion de types déclarative sur les propriétés du modèle.
 * La conversion est explicite via cast() — pas de __get magique.
 *
 * Types supportés :
 *   'int' | 'integer'
 *   'float' | 'double'
 *   'bool' | 'boolean'
 *   'string'
 *   'array'        → json_decode / json_encode
 *   'datetime'     → DateTimeImmutable (format Y-m-d H:i:s)
 *   'date'         → DateTimeImmutable (format Y-m-d, heure à minuit)
 *   'timestamp'    → int (Unix timestamp)
 *
 * Usage dans le modèle :
 *
 *   protected array $casts = [
 *       'created_at'   => 'datetime',
 *       'published_at' => 'date',
 *       'is_active'    => 'bool',
 *       'score'        => 'float',
 *       'meta'         => 'array',
 *   ];
 *
 * Usage :
 *
 *   $article->cast('created_at');          // DateTimeImmutable
 *   $article->cast('is_active');           // bool
 *   $article->castAll();                   // array avec tous les champs castés
 */
trait HasCasts
{
    /**
     * Déclaration des casts.
     * À surcharger dans chaque modèle.
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Retourne la valeur d'un champ après application du cast déclaré.
     * Si aucun cast n'est déclaré pour ce champ, retourne la valeur brute.
     *
     * @throws CastException si le type de cast est inconnu
     */
    public function cast(string $field): mixed
    {
        if (!property_exists($this, $field)) {
            return null;
        }

        $value = $this->{$field};

        if (!isset($this->casts[$field])) {
            return $value;
        }

        return $this->applyCast($value, $this->casts[$field], $field);
    }

    /**
     * Retourne un tableau de tous les champs avec leurs casts appliqués.
     * Les champs sans cast déclaré sont retournés tels quels.
     *
     * @return array<string, mixed>
     */
    public function castAll(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            // Exclure les propriétés internes du trait/modèle
            if (in_array($key, ['fillable', 'casts', 'hidden', 'accessors', 'relations'], strict: true)) {
                continue;
            }

            $result[$key] = isset($this->casts[$key])
                ? $this->applyCast($value, $this->casts[$key], $key)
                : $value;
        }

        return $result;
    }

    /**
     * Indique si un champ possède un cast déclaré.
     */
    public function hasCast(string $field): bool
    {
        return isset($this->casts[$field]);
    }

    /**
     * Retourne le type de cast déclaré pour un champ, ou null.
     */
    public function getCastType(string $field): ?string
    {
        return $this->casts[$field] ?? null;
    }

    /**
     * Applique la conversion de type sur une valeur brute.
     *
     * @throws CastException
     */
    private function applyCast(mixed $value, string $type, string $field): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer'   => (int) $value,
            'float', 'double'  => (float) $value,
            'bool', 'boolean'  => (bool) $value,
            'string'           => (string) $value,

            'array' => match (true) {
                is_array($value)  => $value,
                is_string($value) => json_decode($value, associative: true) ?? [],
                default           => [],
            },

            'datetime' => match (true) {
                $value instanceof DateTimeInterface => DateTimeImmutable::createFromInterface($value),
                is_string($value)                  => new DateTimeImmutable($value),
                is_int($value)                     => (new DateTimeImmutable())->setTimestamp($value),
                default                            => throw new CastException("Impossible de caster '{$field}' en datetime."),
            },

            'date' => match (true) {
                $value instanceof DateTimeInterface => DateTimeImmutable::createFromInterface($value)->setTime(0, 0),
                is_string($value)                  => new DateTimeImmutable($value . ' 00:00:00'),
                default                            => throw new CastException("Impossible de caster '{$field}' en date."),
            },

            'timestamp' => match (true) {
                is_int($value)                     => $value,
                $value instanceof DateTimeInterface => $value->getTimestamp(),
                is_string($value)                  => (new DateTimeImmutable($value))->getTimestamp(),
                default                            => throw new CastException("Impossible de caster '{$field}' en timestamp."),
            },

            default => throw new CastException("Type de cast inconnu '{$type}' pour le champ '{$field}'."),
        };
    }
}
