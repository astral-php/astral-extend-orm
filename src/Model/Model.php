<?php

declare(strict_types=1);

namespace AstralOrm\Model;

use AstralOrm\Model\Traits\HasAccessors;
use AstralOrm\Model\Traits\HasCasts;
use AstralOrm\Model\Traits\HasFillable;
use AstralOrm\Model\Traits\HasHidden;
use AstralOrm\Model\Traits\HasRelations;

/**
 * Model — Classe de base enrichie pour Astral MVC
 *
 * Remplace les modèles anémiques d'Astral en leur ajoutant :
 *   - Hydratation de masse sécurisée (HasFillable)
 *   - Conversion de types déclarative (HasCasts)
 *   - Accessors et Mutators par convention (HasAccessors)
 *   - Sérialisation contrôlée toArray/toJson (HasHidden)
 *   - Relations déclaratives sans lazy-loading (HasRelations)
 *
 * Les modèles existants peuvent hériter de cette classe
 * sans aucune modification du reste du framework.
 *
 * Compatibilité ascendante totale :
 *   Les DAOs, migrations et routes ne changent pas.
 *   PDO::FETCH_CLASS continue de fonctionner normalement.
 *
 * Exemple minimal :
 *
 *   final class Article extends Model
 *   {
 *       public int    $id          = 0;
 *       public string $title       = '';
 *       public string $body        = '';
 *       public int    $category_id = 0;
 *       public string $created_at  = '';
 *
 *       protected array $fillable = ['title', 'body', 'category_id'];
 *       protected array $casts    = ['created_at' => 'datetime', 'is_published' => 'bool'];
 *       protected array $hidden   = [];
 *
 *       public function category(): BelongsTo
 *       {
 *           return $this->belongsTo(Category::class, 'category_id');
 *       }
 *   }
 */
abstract class Model
{
    use HasFillable;
    use HasCasts;
    use HasAccessors;
    use HasHidden;
    use HasRelations;

    /**
     * Crée une nouvelle instance et l'hydrate avec les données fournies.
     * Alias statique de (new static())->fill($data).
     *
     * @param array<string, mixed> $data
     */
    public static function make(array $data): static
    {
        return (new static())->fill($data);
    }

    /**
     * Crée une collection d'instances à partir d'un tableau de tableaux.
     * Utile pour hydrater des résultats bruts PDO (FETCH_ASSOC).
     *
     * @param array<array<string, mixed>> $rows
     * @return list<static>
     */
    public static function collection(array $rows): array
    {
        return array_map(fn(array $row) => static::make($row), $rows);
    }
}
