<?php

declare(strict_types=1);

namespace AstralOrm\Model\Traits;

use ReflectionNamedType;
use ReflectionProperty;

/**
 * Trait HasFillable
 *
 * Permet l'hydratation de masse sécurisée via une liste blanche $fillable.
 * Les champs non déclarés dans $fillable sont ignorés silencieusement.
 *
 * Les valeurs sont coercées vers le type déclaré de la propriété (int, float,
 * bool, string) pour rester compatible avec les modèles typés PHP 8
 * et les entrées formulaire / PDO (souvent des strings).
 *
 * Usage dans le modèle :
 *
 *   protected array $fillable = ['title', 'body', 'category_id'];
 *
 * Usage :
 *
 *   $article = new Article();
 *   $article->fill($request->body);
 *
 *   // ou via la méthode statique du Model de base
 *   $article = Article::make($request->body);
 */
trait HasFillable
{
    /**
     * Liste des propriétés autorisées à l'hydratation de masse.
     * À déclarer dans chaque modèle.
     *
     * @var array<string>
     */
    protected array $fillable = [];

    /**
     * Hydrate l'instance avec les données fournies,
     * en respectant la liste blanche $fillable.
     *
     * @param array<string, mixed> $data
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if ($this->isFillable($key)) {
                $this->{$key} = $this->coerceForProperty($key, $value);
            }
        }

        return $this;
    }

    /**
     * Indique si un champ est autorisé à l'hydratation de masse.
     */
    public function isFillable(string $key): bool
    {
        return in_array($key, $this->fillable, strict: true);
    }

    /**
     * Retourne la liste des champs fillable déclarés.
     *
     * @return array<string>
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Coerce une valeur vers le type natif déclaré sur la propriété, si possible.
     */
    private function coerceForProperty(string $key, mixed $value): mixed
    {
        if ($value === null || !property_exists($this, $key)) {
            return $value;
        }

        $type = (new ReflectionProperty($this, $key))->getType();

        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => match (true) {
                is_bool($value) => $value,
                is_int($value), is_float($value) => $value != 0,
                is_string($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                default => (bool) $value,
            },
            default => $value,
        };
    }
}
