# astral-php/astral-extend-orm

Extension ORM pour [Astral](https://github.com/astral-php) — Enrichit les modèles existants sans toucher au framework.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![Tests](https://img.shields.io/badge/tests-PHPUnit%2010%2B-9933CC)](./phpunit.xml)

---

## À propos

`astral-extend-orm` est une extension **opt-in** pour Astral MVC.  
Elle ajoute sur les modèles les capacités qui manquent au système anémique de base :

| Feature | Description |
|---|---|
| **`HasFillable`** | Hydratation de masse sécurisée via liste blanche |
| **`HasCasts`** | Conversion de types déclarative (`datetime`, `bool`, `array`…) |
| **`HasAccessors`** | Accessors et Mutators par convention de nommage |
| **`HasHidden`** | Sérialisation contrôlée `toArray()` / `toJson()` avec `$hidden` / `$visible` |
| **`HasRelations`** | Relations déclaratives (`belongsTo`, `hasMany`) — sans lazy-loading |

**Philosophie :**
- Zéro magie (`__get`/`__set` non surchargés)
- Compatibilité ascendante totale — DAOs, migrations, routes inchangés
- Opt-in par modèle — chaque modèle choisit ses traits
- Dans l'esprit Astral : explicite, lisible, testable

---

## Installation

```bash
composer require astral-php/astral-extend-orm
```

> Requiert PHP 8.1+ et une app basée sur `astral-php/astral-core` ^1.2.

---

## Démarrage rapide

### 1. Faire hériter votre modèle de `Model`

Remplacez la classe parente de vos modèles Astral :

```php
<?php
// app/Models/Article.php

declare(strict_types=1);

namespace App\Models;

use AstralOrm\Model\Model;
use AstralOrm\Relations\BelongsTo;
use AstralOrm\Relations\HasMany;

final class Article extends Model  // ← était : class Article (anémique)
{
    // Propriétés inchangées — compatibilité PDO::FETCH_CLASS préservée
    public int    $id          = 0;
    public string $title       = '';
    public string $body        = '';
    public string $slug        = '';
    public int    $category_id = 0;
    public int    $is_published = 0;
    public string $created_at  = '';

    // --- Configuration des traits ---

    protected array $fillable = ['title', 'body', 'slug', 'category_id', 'is_published'];

    protected array $casts = [
        'is_published' => 'bool',
        'created_at'   => 'datetime',
    ];

    protected array $hidden = [];  // rien à cacher sur Article

    // --- Relations ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
```

### 2. Aucune modification requise ailleurs

Les DAOs, les migrations, les routes et les providers **restent inchangés**.  
`PDO::FETCH_CLASS` continue de fonctionner normalement puisque `Model` est une
classe PHP ordinaire avec des propriétés publiques.

---

## Référence complète

---

### HasFillable — Hydratation de masse

Permet d'hydrater un modèle depuis un tableau (ex: `$request->body`) en n'autorisant
que les champs déclarés dans `$fillable`.

#### Configuration

```php
protected array $fillable = ['title', 'body', 'slug', 'category_id'];
```

#### Usage

```php
// Dans un contrôleur — store()
$article = new Article();
$article->fill($request->body);

// Ou via la méthode statique (équivalent)
$article = Article::make($request->body);

// Créer une collection depuis des résultats bruts PDO (FETCH_ASSOC)
$articles = Article::collection($pdo->query('SELECT * FROM articles')->fetchAll());
```

#### API

| Méthode | Description |
|---|---|
| `fill(array $data): static` | Hydrate l'instance, retourne `$this` |
| `isFillable(string $key): bool` | Vérifie si un champ est dans `$fillable` |
| `getFillable(): array` | Retourne la liste `$fillable` |
| `static make(array $data): static` | Crée + hydrate en une ligne |
| `static collection(array $rows): array` | Crée une liste d'instances depuis un tableau de tableaux |

> Les champs absents de `$fillable` sont **ignorés silencieusement**.  
> `id`, `created_at`, `updated_at` ne doivent généralement pas être dans `$fillable`.

---

### HasCasts — Conversion de types

Déclare les conversions de types sur les propriétés du modèle.  
Approche **explicite** : `cast()` — pas de `__get` magique.

#### Types supportés

| Type | Conversion |
|---|---|
| `'int'` / `'integer'` | `(int)` |
| `'float'` / `'double'` | `(float)` |
| `'bool'` / `'boolean'` | `(bool)` |
| `'string'` | `(string)` |
| `'array'` | `json_decode()` si string, sinon retour direct |
| `'datetime'` | `DateTimeImmutable` depuis string, int (timestamp) ou DateTimeInterface |
| `'date'` | `DateTimeImmutable` à minuit |
| `'timestamp'` | `int` Unix timestamp |

#### Configuration

```php
protected array $casts = [
    'created_at'   => 'datetime',
    'published_at' => 'date',
    'is_published' => 'bool',
    'view_count'   => 'int',
    'score'        => 'float',
    'meta'         => 'array',   // colonne JSON en base
];
```

#### Usage

```php
$article = $articleDao->findById(42);

// Accès casté explicite
$date    = $article->cast('created_at');   // DateTimeImmutable
$active  = $article->cast('is_published'); // bool
$meta    = $article->cast('meta');         // array

// Dans une vue
<?= $article->cast('created_at')->format('d/m/Y') ?>

// Tous les champs avec leurs casts appliqués
$data = $article->castAll();  // array<string, mixed>
```

#### API

| Méthode | Description |
|---|---|
| `cast(string $field): mixed` | Retourne la valeur castée (ou brute si pas de cast déclaré) |
| `castAll(): array` | Retourne tous les champs avec casts appliqués |
| `hasCast(string $field): bool` | Vérifie si un cast est déclaré |
| `getCastType(string $field): ?string` | Retourne le type de cast déclaré |

> `cast()` retourne `null` si la valeur de la propriété est `null`.  
> Une `CastException` est levée si le type de cast est inconnu.

---

### HasAccessors — Accessors & Mutators

Définit des champs calculés (accessors) et des transformations à l'écriture (mutators)
par convention de nommage.  
Approche **explicite** : `get()` / `set()` — pas de `__get`/`__set`.

#### Convention

```
Accessor  →  get{FieldName}Attribute()   →  appelé par  $model->get('field_name')
Mutator   →  set{FieldName}Attribute()   →  appelé par  $model->set('field_name', $value)
```

`field_name` (snake_case) est converti en `FieldName` (StudlyCase) automatiquement.

#### Exemple — Modèle User

```php
final class User extends Model
{
    public string $first_name = '';
    public string $last_name  = '';
    public string $password   = '';
    public string $slug       = '';

    // Accessor : champ calculé (pas de colonne en base)
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Mutator : hashage automatique
    public function setPasswordAttribute(string $value): void
    {
        $this->password = password_hash($value, PASSWORD_BCRYPT);
    }

    // Mutator : normalisation du slug
    public function setSlugAttribute(string $value): void
    {
        $this->slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    }
}
```

#### Usage

```php
$user = new User();
$user->first_name = 'Jean';
$user->last_name  = 'Dupont';

$user->get('full_name');           // 'Jean Dupont' — via getFullNameAttribute()
$user->get('first_name');          // 'Jean'        — propriété directe (pas d'accessor)

$user->set('password', 'secret'); // hash automatique via setPasswordAttribute()
$user->set('first_name', 'Paul'); // assignation directe (pas de mutator)

// Dans un contrôleur — store()
$user = new User();
$user->set('password', $request->body['password']); // hashé automatiquement
```

#### API

| Méthode | Description |
|---|---|
| `get(string $field): mixed` | Accessor si défini, sinon propriété brute |
| `set(string $field, mixed $value): static` | Mutator si défini, sinon assignation directe |
| `hasAccessor(string $field): bool` | Vérifie si un accessor existe |
| `hasMutator(string $field): bool` | Vérifie si un mutator existe |

---

### HasHidden — Sérialisation contrôlée

Contrôle les champs exposés lors de la sérialisation en tableau ou JSON.  
Essentiel pour les réponses API : éviter d'exposer `password`, `remember_token`, etc.

#### Mode `$hidden` (liste noire — recommandé)

```php
protected array $hidden = ['password', 'remember_token', 'api_token'];
```

#### Mode `$visible` (liste blanche — strict)

```php
// Prioritaire sur $hidden si les deux sont définis
protected array $visible = ['id', 'name', 'email', 'created_at'];
```

#### Usage

```php
// Dans un contrôleur API
$user = $userDao->findById(1);

$this->json($user->toArray());        // sans password, remember_token
$this->json($user->toJson());         // idem en JSON string

// Exclure un champ supplémentaire ponctuellement
$user->toArray(['email']);            // sans password ET sans email

// Accéder à tous les champs (override ponctuel)
$user->withHidden();                  // avec password et remember_token

// Dans un contrôleur API — réponse liste
$users = $userDao->findAll();
$data  = array_map(fn(User $u) => $u->toArray(), $users);
$this->json(['data' => $data]);
```

#### API

| Méthode | Description |
|---|---|
| `toArray(array $except = []): array` | Tableau filtré selon `$visible` / `$hidden` |
| `toJson(int $flags = 0): string` | JSON filtré (accepte `JSON_PRETTY_PRINT` etc.) |
| `withHidden(): array` | Tableau complet sans aucune exclusion |

> Les propriétés internes des traits (`fillable`, `casts`, `hidden`, `visible`) sont
> **toujours exclues** de `toArray()` et `withHidden()`.

---

### HasRelations — Relations déclaratives

Déclare les relations sur le modèle (sens sémantique), résout explicitement dans le
contrôleur ou le DAO (pas de lazy-loading, pas de N+1 involontaire).

#### BelongsTo — N→1

```php
// Sur le modèle
public function category(): BelongsTo
{
    return $this->belongsTo(
        relatedClass: Category::class,
        foreignKey:   'category_id',      // clé sur CE modèle
        // ownerTable: auto-déduite → 'categories'
        // ownerKey:   'id' (défaut)
    );
}
```

```php
// Dans un contrôleur ou DAO — résolution explicite
$article  = $articleDao->findById(42);
$category = $article->category()->resolve($pdo);  // → Category|null
```

#### HasMany — 1→N

```php
// Sur le modèle
public function articles(): HasMany
{
    return $this->hasMany(
        relatedClass: Article::class,
        foreignKey:   'category_id',     // clé sur le modèle lié
        // relatedTable: auto-déduite → 'articles'
        // localKey:    'id' (défaut)
        // orderBy:     'id' (défaut)
        // direction:   'ASC' (défaut)
    );
}
```

```php
// Résolution explicite
$category = $categoryDao->findById(3);
$articles = $category->articles()->resolve($pdo);  // → Article[]
```

#### Eager Loading — évite le problème N+1

Pour charger les relations d'une liste d'entités sans N+1, utilisez `HasMany::eagerLoad()` :

```php
// Dans un DAO ou contrôleur
$categories = $categoryDao->findAll();   // 1 requête

// Charge tous les articles de toutes les catégories en 1 requête supplémentaire
// et injecte la propriété 'articles' sur chaque Category
$categories = HasMany::eagerLoad(
    parents:      $categories,
    relatedClass: Article::class,
    foreignKey:   'category_id',
    propertyName: 'articles',          // propriété dynamique injectée sur chaque parent
    pdo:          $pdo,
    // table:     'articles'           // auto-déduit si omis
    // orderBy:   'created_at'
    // direction: 'DESC'
);

// Dans la vue
foreach ($categories as $category) {
    foreach ($category->articles as $article) {  // propriété injectée
        echo $article->title;
    }
}
```

Total : **2 requêtes** quelle que soit la taille de la liste.

#### Auto-déduction du nom de table

`belongsTo` et `hasMany` devinent le nom de table si vous ne le fournissez pas :

| Classe | Table déduite |
|---|---|
| `Category` | `categories` |
| `Article` | `articles` |
| `Tag` | `tags` |
| `CategoryType` | `category_types` |

> Si votre table ne suit pas cette convention, passez le nom explicitement.

#### API

**`BelongsTo`**

| Méthode | Description |
|---|---|
| `resolve(PDO $pdo): ?object` | Exécute la requête, retourne l'entité ou null |

**`HasMany`**

| Méthode | Description |
|---|---|
| `resolve(PDO $pdo): array` | Exécute la requête, retourne la liste |
| `static eagerLoad(...): array` | Charge les relations de toute une liste (2 requêtes) |

---

## Exemples complets

### Modèle Article complet

```php
<?php
declare(strict_types=1);

namespace App\Models;

use AstralOrm\Model\Model;
use AstralOrm\Relations\BelongsTo;

final class Article extends Model
{
    public int    $id          = 0;
    public string $title       = '';
    public string $body        = '';
    public string $slug        = '';
    public int    $category_id = 0;
    public int    $user_id     = 0;
    public int    $is_published = 0;
    public string $created_at  = '';

    protected array $fillable = ['title', 'body', 'slug', 'category_id', 'is_published'];

    protected array $casts = [
        'is_published' => 'bool',
        'created_at'   => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'users');
    }

    // Mutator : normalisation du slug
    public function setSlugAttribute(string $value): void
    {
        $this->slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    }

    // Accessor : extrait des 150 premiers caractères du body
    public function getExcerptAttribute(): string
    {
        return mb_strimwidth(strip_tags($this->body), 0, 150, '…');
    }
}
```

### Modèle User complet

```php
<?php
declare(strict_types=1);

namespace App\Models;

use AstralOrm\Model\Model;
use AstralOrm\Relations\HasMany;

final class User extends Model
{
    public int    $id             = 0;
    public string $first_name     = '';
    public string $last_name      = '';
    public string $email          = '';
    public string $password       = '';
    public string $remember_token = '';
    public string $role           = 'user';
    public string $created_at     = '';

    protected array $fillable = ['first_name', 'last_name', 'email', 'role'];

    protected array $casts = [
        'created_at' => 'datetime',
    ];

    protected array $hidden = ['password', 'remember_token'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'user_id');
    }

    // Accessor
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Mutator
    public function setPasswordAttribute(string $value): void
    {
        $this->password = password_hash($value, PASSWORD_BCRYPT);
    }
}
```

### Contrôleur — store() avec fill

```php
public function store(Request $request): Response
{
    $v = $this->validate($request->body, [
        'title' => 'required|min:3|max:255',
        'body'  => 'required|min:10',
    ]);

    if ($v->fails()) {
        return $this->render('articles/create', ['errors' => $v->errors()]);
    }

    $article = Article::make($request->body);  // fill() sécurisé
    $article->set('slug', $request->body['title']); // mutator normalise
    $article->created_at = date('Y-m-d H:i:s');

    $id = $this->articleDao->insert((array) $article);

    $this->session->flash('success', 'Article créé.');
    return $this->redirect('/articles/' . $id);
}
```

### Contrôleur API — réponse sérialisée

```php
public function index(Request $request): JsonResponse
{
    $result   = $this->articleDao->paginate(page: 1, perPage: 15);
    $articles = HasMany::eagerLoad(
        parents:      $result['data'],
        relatedClass: Category::class,
        foreignKey:   'category_id',
        propertyName: 'category',
        pdo:          $this->pdo,
        table:        'categories',
    );

    $data = array_map(fn(Article $a) => array_merge(
        $a->toArray(),
        [
            'created_at' => $a->cast('created_at')?->format('d/m/Y H:i'),
            'excerpt'    => $a->get('excerpt'),  // accessor
            'category'   => $a->category ?? null,
        ]
    ), $articles);

    return $this->paginated($data, $result);
}
```

### Vue — affichage avec cast

```php
<?php /** @var Article $article */ ?>

<h1><?= htmlspecialchars($article->title) ?></h1>

<p class="meta">
    Publié le <?= $article->cast('created_at')->format('d/m/Y à H:i') ?>
</p>

<p class="excerpt">
    <?= htmlspecialchars($article->get('excerpt')) ?>
</p>

<?php if ($article->cast('is_published')): ?>
    <span class="badge">Publié</span>
<?php endif ?>
```

---

## Utilisation des traits individuellement

Si vous ne souhaitez pas hériter de `Model`, vous pouvez utiliser chaque trait séparément
sur vos propres classes :

```php
use AstralOrm\Model\Traits\HasFillable;
use AstralOrm\Model\Traits\HasCasts;
use AstralOrm\Model\Traits\HasHidden;

final class Article
{
    use HasFillable;
    use HasCasts;
    use HasHidden;

    public int    $id    = 0;
    public string $title = '';
    // ...

    protected array $fillable = ['title', 'body'];
    protected array $casts    = ['created_at' => 'datetime'];
    protected array $hidden   = [];
}
```

---

## Tests

```bash
composer install
vendor/bin/phpunit
```

```
AstralOrm\Tests\Model\HasFillableTest
 ✔ Fill hydrates allowed fields
 ✔ Fill ignores non fillable fields
 ✔ Fill returns same instance
 ✔ Is fillable returns true for allowed field
 ✔ Make creates hydrated instance
 ✔ Get fillable returns declared list
 ✔ Collection creates multiple instances

AstralOrm\Tests\Model\HasCastsTest
 ✔ Cast int
 ✔ Cast float
 ✔ Cast bool
 ✔ Cast datetime from string
 ✔ Cast date has midnight time
 ✔ Cast array from json string
 ✔ Cast returns raw value when no cast declared
 ✔ Cast returns null for null value
 ✔ Cast unknown type throws exception
 ✔ Has cast
 ✔ Cast all returns all fields

AstralOrm\Tests\Model\HasAccessorsTest
 ✔ Get calls accessor when defined
 ✔ Get returns property when no accessor
 ✔ Get returns null for unknown field
 ✔ Set calls mutator when defined
 ✔ Set normalizes slug via mutator
 ✔ Set assigns directly when no mutator
 ✔ Set returns same instance
 ✔ Has accessor
 ✔ Has mutator

AstralOrm\Tests\Model\HasHiddenTest
 ✔ To array excludes hidden fields
 ✔ To array with visible includes only visible fields
 ✔ To array with extra except fields
 ✔ To json returns valid json
 ✔ With hidden returns all fields
 ✔ To array does not include trait internal properties

AstralOrm\Tests\Relations\RelationsTest
 ✔ Belongs to resolve returns related entity
 ✔ Belongs to resolve returns null when foreign id is zero
 ✔ Belongs to resolve returns null when not found
 ✔ Belongs to getters
 ✔ Has many resolve returns related entities
 ✔ Has many resolve returns empty array when local id is zero
 ✔ Has many resolve returns empty for no results
 ✔ Eager load attaches related entities to parents
 ✔ Eager load with empty parents returns empty
 ✔ Eager load sets empty array when no children
```

---

## Structure du dépôt

```
astral-extend-orm/
├── src/
│   ├── Model/
│   │   ├── Model.php                  ← Classe de base (hérite de cette classe)
│   │   └── Traits/
│   │       ├── HasFillable.php        ← Hydratation de masse sécurisée
│   │       ├── HasCasts.php           ← Conversion de types déclarative
│   │       ├── HasAccessors.php       ← Accessors & Mutators par convention
│   │       ├── HasHidden.php          ← toArray() / toJson() avec $hidden / $visible
│   │       └── HasRelations.php       ← belongsTo() / hasMany() déclaratifs
│   ├── Relations/
│   │   ├── BelongsTo.php              ← Descripteur de relation N→1
│   │   └── HasMany.php                ← Descripteur de relation 1→N + eager loading
│   └── Exceptions/
│       └── CastException.php          ← Levée si cast inconnu ou impossible
├── tests/
│   ├── Model/
│   │   ├── HasFillableTest.php
│   │   ├── HasCastsTest.php
│   │   ├── HasAccessorsTest.php
│   │   └── HasHiddenTest.php
│   └── Relations/
│       └── RelationsTest.php          ← SQLite in-memory
├── composer.json
├── phpunit.xml
├── LICENSE
└── README.md
```

---

## Tests

```bash
composer test
# ou
vendor/bin/phpunit
```

Suite obligatoire avant publication Packagist / tag.

---

## Compatibilité

| astral-core | PHP | astral-extend-orm |
|---|---|---|
| ^1.2 | 8.1 → 8.4 | 0.x |

---

## Licence

MIT — voir [LICENSE](./LICENSE)
