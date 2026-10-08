<?php

declare(strict_types=1);

namespace AstralOrm\Relations;

use PDO;

/**
 * Relation BelongsTo — N→1
 *
 * Objet descripteur d'une relation "appartient à".
 * Ne déclenche aucune requête tant que resolve() n'est pas appelé.
 *
 * Exemple :
 *   $article->category()->resolve($pdo);   // → Category|null
 */
final class BelongsTo
{
    public function __construct(
        private readonly string $relatedClass,
        private readonly string $table,
        private readonly int    $foreignId,
        private readonly string $ownerKey = 'id',
    ) {}

    /**
     * Exécute la requête et retourne l'entité liée, ou null.
     *
     * @return object|null Instance de $relatedClass ou null
     */
    public function resolve(PDO $pdo): ?object
    {
        if ($this->foreignId <= 0) {
            return null;
        }

        $sql  = "SELECT * FROM {$this->table} WHERE {$this->ownerKey} = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $this->foreignId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->relatedClass);

        $result = $stmt->fetch();

        return $result instanceof $this->relatedClass ? $result : null;
    }

    // ── Getters pour debug / introspection ──────────────────────────────────

    public function getRelatedClass(): string { return $this->relatedClass; }
    public function getTable(): string        { return $this->table; }
    public function getForeignId(): int       { return $this->foreignId; }
    public function getOwnerKey(): string     { return $this->ownerKey; }
}
