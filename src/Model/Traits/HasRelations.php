<?php

declare(strict_types=1);

namespace AstralOrm\Model\Traits;

use AstralOrm\Relations\BelongsTo;
use AstralOrm\Relations\HasMany;

/**
 * Trait HasRelations
 *
 * Déclare les relations directement sur le modèle.
 * Les relations sont des objets descripteurs — elles ne déclenchent
 * AUCUNE requête tant que ->resolve($pdo) n'est pas appelé explicitement.
 *
 * Philosophie Astral : pas de lazy-loading, pas de magie.
 * La définition vit sur le modèle (sens sémantique),
 * la résolution reste explicite dans le contrôleur ou le DAO.
 *
 * Usage dans le modèle :
 *
 *   public function category(): BelongsTo
 *   {
 *       return $this->belongsTo(Category::class, 'category_id');
 *   }
 *
 *   public function tags(): HasMany
 *   {
 *       return $this->hasMany(Tag::class, 'article_id');
 *   }
 *
 * Usage dans un contrôleur ou DAO :
 *
 *   $article  = $articleDao->findById(42);
 *   $category = $article->category()->resolve($pdo);
 *
 *   $articles = $articleDao->findAll();
 *   $articles  = HasMany::eagerLoad($articles, Tag::class, 'article_id', 'tags', $pdo);
 */
trait HasRelations
{
    /**
     * Déclare une relation BelongsTo (N→1).
     *
     * @param class-string $relatedClass   Classe du modèle lié
     * @param string       $foreignKey     Clé étrangère sur CE modèle (ex: 'category_id')
     * @param string       $ownerTable     Table du modèle lié (auto-déduite si omis)
     * @param string       $ownerKey       Clé primaire du modèle lié (défaut: 'id')
     */
    public function belongsTo(
        string $relatedClass,
        string $foreignKey,
        string $ownerTable = '',
        string $ownerKey = 'id',
    ): BelongsTo {
        $foreignId = property_exists($this, $foreignKey)
            ? (int) $this->{$foreignKey}
            : 0;

        // Auto-déduction du nom de table si non fourni
        if ($ownerTable === '') {
            $ownerTable = $this->guessTable($relatedClass);
        }

        return new BelongsTo(
            relatedClass: $relatedClass,
            table:        $ownerTable,
            foreignId:    $foreignId,
            ownerKey:     $ownerKey,
        );
    }

    /**
     * Déclare une relation HasMany (1→N).
     *
     * @param class-string $relatedClass   Classe du modèle lié
     * @param string       $foreignKey     Clé étrangère sur le modèle lié (ex: 'article_id')
     * @param string       $relatedTable   Table du modèle lié (auto-déduite si omis)
     * @param string       $localKey       Clé primaire locale (défaut: 'id')
     * @param string       $orderBy        Colonne de tri (défaut: 'id')
     * @param string       $direction      Direction du tri (ASC | DESC)
     */
    public function hasMany(
        string $relatedClass,
        string $foreignKey,
        string $relatedTable = '',
        string $localKey = 'id',
        string $orderBy = 'id',
        string $direction = 'ASC',
    ): HasMany {
        $localId = property_exists($this, $localKey)
            ? (int) $this->{$localKey}
            : 0;

        if ($relatedTable === '') {
            $relatedTable = $this->guessTable($relatedClass);
        }

        return new HasMany(
            relatedClass:  $relatedClass,
            table:         $relatedTable,
            foreignKey:    $foreignKey,
            localId:       $localId,
            orderBy:       $orderBy,
            direction:     $direction,
        );
    }

    /**
     * Déduit le nom de table à partir du nom de classe.
     * Ex: App\Models\Category → 'categories'
     *     App\Models\Article  → 'articles'
     */
    private function guessTable(string $class): string
    {
        $parts     = explode('\\', $class);
        $shortName = strtolower(end($parts));

        // Pluralisation simple (suffisant pour la majorité des cas)
        return match (true) {
            str_ends_with($shortName, 'y')  => substr($shortName, 0, -1) . 'ies',
            str_ends_with($shortName, 's')  => $shortName,
            default                          => $shortName . 's',
        };
    }
}
