<?php

declare(strict_types=1);

namespace AstralOrm\Model\Traits;

/**
 * Trait HasAccessors
 *
 * Accessors et Mutators déclaratifs par convention de nommage.
 * Approche explicite : get() et set() — pas de surcharge __get/__set.
 *
 * Convention :
 *   Accessor  → getXxxAttribute()   appelé par  $model->get('xxx')
 *   Mutator   → setXxxAttribute()   appelé par  $model->set('xxx', $value)
 *
 * Exemple dans le modèle :
 *
 *   // Accessor : prénom + nom concaténés
 *   public function getFullNameAttribute(): string
 *   {
 *       return trim($this->first_name . ' ' . $this->last_name);
 *   }
 *
 *   // Mutator : hashage automatique du mot de passe
 *   public function setPasswordAttribute(string $value): void
 *   {
 *       $this->password = password_hash($value, PASSWORD_BCRYPT);
 *   }
 *
 *   // Mutator : normalisation du slug
 *   public function setSlugAttribute(string $value): void
 *   {
 *       $this->slug = strtolower(str_replace(' ', '-', $value));
 *   }
 *
 * Usage :
 *
 *   $user->get('full_name');               // appelle getFullNameAttribute()
 *   $user->set('password', 'plain123');    // appelle setPasswordAttribute()
 *   $user->get('email');                   // retourne $user->email directement (pas d'accessor)
 *   $user->set('title', 'Mon titre');      // assigne $user->title directement (pas de mutator)
 */
trait HasAccessors
{
    /**
     * Accède à un champ via son accessor s'il existe,
     * ou retourne directement la propriété.
     */
    public function get(string $field): mixed
    {
        $method = $this->buildAccessorName($field);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return property_exists($this, $field) ? $this->{$field} : null;
    }

    /**
     * Assigne une valeur via son mutator s'il existe,
     * ou assigne directement la propriété.
     */
    public function set(string $field, mixed $value): static
    {
        $method = $this->buildMutatorName($field);

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } elseif (property_exists($this, $field)) {
            $this->{$field} = $value;
        }

        return $this;
    }

    /**
     * Indique si un accessor existe pour ce champ.
     */
    public function hasAccessor(string $field): bool
    {
        return method_exists($this, $this->buildAccessorName($field));
    }

    /**
     * Indique si un mutator existe pour ce champ.
     */
    public function hasMutator(string $field): bool
    {
        return method_exists($this, $this->buildMutatorName($field));
    }

    /**
     * Construit le nom de la méthode accessor pour un champ.
     * Ex: 'full_name' → 'getFullNameAttribute'
     */
    private function buildAccessorName(string $field): string
    {
        return 'get' . $this->studlyCase($field) . 'Attribute';
    }

    /**
     * Construit le nom de la méthode mutator pour un champ.
     * Ex: 'password' → 'setPasswordAttribute'
     */
    private function buildMutatorName(string $field): string
    {
        return 'set' . $this->studlyCase($field) . 'Attribute';
    }

    /**
     * Convertit un nom snake_case en StudlyCase.
     * Ex: 'full_name' → 'FullName'
     */
    private function studlyCase(string $value): string
    {
        return str_replace('_', '', ucwords($value, '_'));
    }
}
