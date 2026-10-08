<?php

declare(strict_types=1);

namespace AstralOrm\Model\Traits;

/**
 * Trait HasHidden
 *
 * Sérialisation contrôlée du modèle via $hidden et $visible.
 * Très utile pour les réponses API : exclure password, remember_token, etc.
 *
 * $hidden  → liste de champs à exclure de toArray() / toJson()
 * $visible → liste de champs à inclure UNIQUEMENT (prioritaire sur $hidden si les deux sont définis)
 *
 * Usage dans le modèle :
 *
 *   protected array $hidden = ['password', 'remember_token'];
 *
 *   // ou (mode liste blanche stricte)
 *   protected array $visible = ['id', 'name', 'email', 'created_at'];
 *
 * Usage :
 *
 *   $user->toArray();            // tableau sans les champs hidden
 *   $user->toJson();             // JSON string sans les champs hidden
 *   $user->toArray(['password']) // exclure un champ supplémentaire ponctuellement
 *   $user->withHidden()          // toArray() avec TOUS les champs (override ponctuel)
 */
trait HasHidden
{
    /**
     * Champs à exclure de la sérialisation.
     *
     * @var array<string>
     */
    protected array $hidden = [];

    /**
     * Champs à inclure exclusivement (prioritaire sur $hidden).
     * Laisser vide pour utiliser $hidden à la place.
     *
     * @var array<string>
     */
    protected array $visible = [];

    /**
     * Sérialise le modèle en tableau associatif,
     * en respectant $visible et $hidden.
     *
     * @param array<string> $except Champs supplémentaires à exclure (ponctuellement)
     * @return array<string, mixed>
     */
    public function toArray(array $except = []): array
    {
        $data = $this->getRawAttributes();

        // Mode liste blanche ($visible prioritaire)
        if (!empty($this->visible)) {
            return array_filter(
                $data,
                fn(string $key) => in_array($key, $this->visible, strict: true),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Mode liste noire ($hidden + $except)
        $excluded = array_merge($this->hidden, $except);

        return array_filter(
            $data,
            fn(string $key) => !in_array($key, $excluded, strict: true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Sérialise le modèle en JSON,
     * en respectant $visible et $hidden.
     *
     * @param int $flags Flags JSON (ex: JSON_PRETTY_PRINT)
     */
    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->toArray(), $flags);

        return $json !== false ? $json : '{}';
    }

    /**
     * Sérialise le modèle en tableau sans aucune exclusion.
     * Permet d'accéder ponctuellement aux champs cachés.
     *
     * @return array<string, mixed>
     */
    public function withHidden(): array
    {
        return $this->getRawAttributes();
    }

    /**
     * Retourne les propriétés internes du modèle
     * en excluant les propriétés de configuration des traits.
     *
     * @return array<string, mixed>
     */
    private function getRawAttributes(): array
    {
        $internalKeys = ['fillable', 'casts', 'hidden', 'visible', 'relations'];

        $raw = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (!in_array($key, $internalKeys, strict: true)) {
                $raw[$key] = $value;
            }
        }

        return $raw;
    }
}
