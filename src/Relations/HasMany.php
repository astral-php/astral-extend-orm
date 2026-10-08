<?php

declare(strict_types=1);

namespace AstralOrm\Relations;

use PDO;

/**
 * Relation HasMany — 1→N
 *
 * Objet descripteur d'une relation "possède plusieurs".
 * Ne déclenche aucune requête tant que resolve() ou eagerLoad() n'est pas appelé.
 *
 * Exemple :
 *   $category->articles()->resolve($pdo);   // → Article[]
 *
 * Eager loading (évite N+1) :
 *   HasMany::eagerLoad($categories, Article::class, 'category_id', 'articles', $pdo);
 */
final class HasMany
{
    public function __construct(
        private readonly string $relatedClass,
        private readonly string $table,
        private readonly string $foreignKey,
        private readonly int    $localId,
        private readonly string $orderBy   = 'id',
        private readonly string $direction = 'ASC',
    ) {}

    /**
     * Exécute la requête et retourne la liste des entités liées.
     *
     * @return list<object> Liste d'instances de $relatedClass
     */
    public function resolve(PDO $pdo): array
    {
        if ($this->localId <= 0) {
            return [];
        }

        $direction = strtoupper($this->direction) === 'DESC' ? 'DESC' : 'ASC';

        $sql  = "SELECT * FROM {$this->table}
                 WHERE {$this->foreignKey} = :local_id
                 ORDER BY {$this->orderBy} {$direction}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':local_id' => $this->localId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->relatedClass);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Eager loading : charge toutes les relations en 2 requêtes (évite N+1).
     *
     * Attache les entités liées directement sur chaque parent
     * dans la propriété dynamique $propertyName.
     *
     * @param list<object>  $parents       Liste d'instances parentes (ex: Category[])
     * @param class-string  $relatedClass  Classe du modèle lié (ex: Article::class)
     * @param string        $foreignKey    Clé étrangère sur le modèle lié (ex: 'category_id')
     * @param string        $propertyName  Propriété à injecter sur chaque parent (ex: 'articles')
     * @param PDO           $pdo
     * @param string        $localKey      Clé primaire du parent (défaut: 'id')
     * @param string        $table         Table du modèle lié (auto-déduite si omis)
     * @param string        $orderBy
     * @param string        $direction
     *
     * @return list<object> Les parents avec la propriété $propertyName injectée
     */
    public static function eagerLoad(
        array  $parents,
        string $relatedClass,
        string $foreignKey,
        string $propertyName,
        PDO    $pdo,
        string $localKey    = 'id',
        string $table       = '',
        string $orderBy     = 'id',
        string $direction   = 'ASC',
    ): array {
        if (empty($parents)) {
            return $parents;
        }

        // Auto-déduction de la table si non fournie
        if ($table === '') {
            $parts = explode('\\', $relatedClass);
            $name  = strtolower(end($parts));
            $table = str_ends_with($name, 's') ? $name : $name . 's';
        }

        // Collecte des IDs des parents
        $ids = array_filter(
            array_map(fn(object $p) => property_exists($p, $localKey) ? (int) $p->{$localKey} : 0, $parents),
            fn(int $id) => $id > 0,
        );

        if (empty($ids)) {
            foreach ($parents as $parent) {
                $parent->{$propertyName} = [];
            }
            return $parents;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $direction    = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $sql  = "SELECT * FROM {$table}
                 WHERE {$foreignKey} IN ({$placeholders})
                 ORDER BY {$orderBy} {$direction}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $stmt->setFetchMode(PDO::FETCH_CLASS, $relatedClass);

        $related = $stmt->fetchAll() ?: [];

        // Groupement par foreignKey
        $grouped = [];
        foreach ($related as $item) {
            $key = property_exists($item, $foreignKey) ? (int) $item->{$foreignKey} : 0;
            $grouped[$key][] = $item;
        }

        // Injection sur chaque parent
        foreach ($parents as $parent) {
            $parentId = property_exists($parent, $localKey) ? (int) $parent->{$localKey} : 0;
            $parent->{$propertyName} = $grouped[$parentId] ?? [];
        }

        return $parents;
    }

    // ── Getters pour debug / introspection ──────────────────────────────────

    public function getRelatedClass(): string { return $this->relatedClass; }
    public function getTable(): string        { return $this->table; }
    public function getForeignKey(): string   { return $this->foreignKey; }
    public function getLocalId(): int         { return $this->localId; }
    public function getOrderBy(): string      { return $this->orderBy; }
    public function getDirection(): string    { return $this->direction; }
}
