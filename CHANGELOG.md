# Changelog

Toutes les modifications notables de ce projet sont documentées ici.

---

## [0.1.1] — 2026-10-08

### Correctif

- `HasFillable::fill()` : coerce les scalaires vers le type déclaré de la propriété (`int` / `float` / `bool` / `string`) pour éviter les `TypeError` PHP 8 avec des entrées formulaire / PDO.

## [0.1.0] — 2026-10-04

### Packaging (hub 1.2.5)

- Nom Packagist : `astral-php/astral-extend-orm` (ex. `extend-orm`).
- PHP minimum **^8.1** (propriétés `readonly` dans les relations).
- Cible apps **astral-core ^1.2** (suggest).

### Ajouté

- `Model` — classe de base enrichie (remplace les modèles anémiques Astral)
- `HasFillable` — hydratation de masse sécurisée via `$fillable`
  - `fill()`, `make()`, `collection()`, `isFillable()`, `getFillable()`
- `HasCasts` — conversion de types déclarative via `$casts`
  - Types : `int`, `float`, `bool`, `string`, `array`, `datetime`, `date`, `timestamp`
  - `cast()`, `castAll()`, `hasCast()`, `getCastType()`
- `HasAccessors` — accessors & mutators par convention `getXxxAttribute` / `setXxxAttribute`
  - `get()`, `set()`, `hasAccessor()`, `hasMutator()`
- `HasHidden` — sérialisation contrôlée via `$hidden` / `$visible`
  - `toArray()`, `toJson()`, `withHidden()`
- `HasRelations` — relations déclaratives sans lazy-loading
  - `belongsTo()`, `hasMany()` avec auto-déduction du nom de table
- `BelongsTo` — relation N→1, `resolve(PDO): ?object`
- `HasMany` — relation 1→N, `resolve(PDO): array` + `eagerLoad()` statique (2 requêtes)
- `CastException` — exception levée sur cast inconnu ou impossible
- Suite de tests PHPUnit complète (40 tests, SQLite in-memory pour les relations)
