<?php

declare(strict_types=1);

namespace AstralOrm\Model\Traits;

/**
 * Trait HasFillable
 *
 * Permet l'hydratation de masse sécurisée via une liste blanche $fillable.
 * Les champs non déclarés dans $fillable sont ignorés silencieusement.
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
 *   $article = Article::fill($request->body);
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
                $this->{$key} = $value;
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
}
