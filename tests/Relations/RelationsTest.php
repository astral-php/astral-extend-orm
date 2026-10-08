<?php

declare(strict_types=1);

namespace AstralOrm\Tests\Relations;

use AstralOrm\Model\Model;
use AstralOrm\Relations\BelongsTo;
use AstralOrm\Relations\HasMany;
use PDO;
use PHPUnit\Framework\TestCase;

// ── Stubs modèles ────────────────────────────────────────────────────────────

final class CategoryRelStub extends Model
{
    public int    $id   = 0;
    public string $name = '';
}

final class ArticleRelStub extends Model
{
    public int    $id          = 0;
    public string $title       = '';
    public int    $category_id = 0;

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryRelStub::class, 'category_id', 'categories');
    }
}

final class CategoryWithArticlesStub extends Model
{
    public int    $id   = 0;
    public string $name = '';

    public function articles(): HasMany
    {
        return $this->hasMany(ArticleRelStub::class, 'category_id', 'articles');
    }
}

// ── Tests ────────────────────────────────────────────────────────────────────

final class RelationsTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE categories (
                id   INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )
        ');

        $this->pdo->exec('
            CREATE TABLE articles (
                id          INTEGER PRIMARY KEY,
                title       TEXT NOT NULL,
                category_id INTEGER NOT NULL
            )
        ');

        $this->pdo->exec("INSERT INTO categories VALUES (1, 'PHP')");
        $this->pdo->exec("INSERT INTO categories VALUES (2, 'JavaScript')");

        $this->pdo->exec("INSERT INTO articles VALUES (1, 'Intro PHP', 1)");
        $this->pdo->exec("INSERT INTO articles VALUES (2, 'PHP avancé', 1)");
        $this->pdo->exec("INSERT INTO articles VALUES (3, 'Intro JS', 2)");
    }

    // ── BelongsTo ────────────────────────────────────────────────────────────

    public function test_belongs_to_resolve_returns_related_entity(): void
    {
        $article              = new ArticleRelStub();
        $article->id          = 1;
        $article->category_id = 1;

        $category = $article->category()->resolve($this->pdo);

        $this->assertInstanceOf(CategoryRelStub::class, $category);
        $this->assertSame(1, (int) $category->id);
        $this->assertSame('PHP', $category->name);
    }

    public function test_belongs_to_resolve_returns_null_when_foreign_id_is_zero(): void
    {
        $article              = new ArticleRelStub();
        $article->category_id = 0;

        $result = $article->category()->resolve($this->pdo);

        $this->assertNull($result);
    }

    public function test_belongs_to_resolve_returns_null_when_not_found(): void
    {
        $article              = new ArticleRelStub();
        $article->category_id = 999;

        $result = $article->category()->resolve($this->pdo);

        $this->assertNull($result);
    }

    public function test_belongs_to_getters(): void
    {
        $relation = new BelongsTo(CategoryRelStub::class, 'categories', 1);

        $this->assertSame(CategoryRelStub::class, $relation->getRelatedClass());
        $this->assertSame('categories', $relation->getTable());
        $this->assertSame(1, $relation->getForeignId());
    }

    // ── HasMany ──────────────────────────────────────────────────────────────

    public function test_has_many_resolve_returns_related_entities(): void
    {
        $category     = new CategoryWithArticlesStub();
        $category->id = 1;

        $articles = $category->articles()->resolve($this->pdo);

        $this->assertCount(2, $articles);
        $this->assertInstanceOf(ArticleRelStub::class, $articles[0]);
    }

    public function test_has_many_resolve_returns_empty_array_when_local_id_is_zero(): void
    {
        $category     = new CategoryWithArticlesStub();
        $category->id = 0;

        $result = $category->articles()->resolve($this->pdo);

        $this->assertSame([], $result);
    }

    public function test_has_many_resolve_returns_empty_for_no_results(): void
    {
        $category     = new CategoryWithArticlesStub();
        $category->id = 999;

        $result = $category->articles()->resolve($this->pdo);

        $this->assertSame([], $result);
    }

    // ── Eager Loading ────────────────────────────────────────────────────────

    public function test_eager_load_attaches_related_entities_to_parents(): void
    {
        $cat1     = new CategoryWithArticlesStub();
        $cat1->id = 1;
        $cat2     = new CategoryWithArticlesStub();
        $cat2->id = 2;

        $categories = HasMany::eagerLoad(
            parents:       [$cat1, $cat2],
            relatedClass:  ArticleRelStub::class,
            foreignKey:    'category_id',
            propertyName:  'articles',
            pdo:           $this->pdo,
            table:         'articles',
        );

        $this->assertCount(2, $categories[0]->articles);
        $this->assertCount(1, $categories[1]->articles);
        $this->assertInstanceOf(ArticleRelStub::class, $categories[0]->articles[0]);
    }

    public function test_eager_load_with_empty_parents_returns_empty(): void
    {
        $result = HasMany::eagerLoad(
            parents:      [],
            relatedClass: ArticleRelStub::class,
            foreignKey:   'category_id',
            propertyName: 'articles',
            pdo:          $this->pdo,
        );

        $this->assertSame([], $result);
    }

    public function test_eager_load_sets_empty_array_when_no_children(): void
    {
        $cat      = new CategoryWithArticlesStub();
        $cat->id  = 999; // inexistante

        $result = HasMany::eagerLoad(
            parents:      [$cat],
            relatedClass: ArticleRelStub::class,
            foreignKey:   'category_id',
            propertyName: 'articles',
            pdo:          $this->pdo,
            table:        'articles',
        );

        $this->assertSame([], $result[0]->articles);
    }
}
