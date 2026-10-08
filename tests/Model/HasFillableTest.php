<?php

declare(strict_types=1);

namespace AstralOrm\Tests\Model;

use AstralOrm\Model\Model;
use PHPUnit\Framework\TestCase;

// Stub modèle pour les tests
final class ArticleStub extends Model
{
    public int    $id          = 0;
    public string $title       = '';
    public string $body        = '';
    public int    $category_id = 0;
    public string $secret      = '';

    protected array $fillable = ['title', 'body', 'category_id'];
}

final class HasFillableTest extends TestCase
{
    public function test_fill_hydrates_allowed_fields(): void
    {
        $article = new ArticleStub();
        $article->fill(['title' => 'Mon titre', 'body' => 'Contenu', 'category_id' => 3]);

        $this->assertSame('Mon titre', $article->title);
        $this->assertSame('Contenu', $article->body);
        $this->assertSame(3, $article->category_id);
    }

    public function test_fill_ignores_non_fillable_fields(): void
    {
        $article = new ArticleStub();
        $article->fill(['title' => 'Test', 'secret' => 'HACK']);

        $this->assertSame('Test', $article->title);
        $this->assertSame('', $article->secret); // non modifié
    }

    public function test_fill_returns_same_instance(): void
    {
        $article = new ArticleStub();
        $returned = $article->fill(['title' => 'Test']);

        $this->assertSame($article, $returned);
    }

    public function test_is_fillable_returns_true_for_allowed_field(): void
    {
        $article = new ArticleStub();
        $this->assertTrue($article->isFillable('title'));
        $this->assertFalse($article->isFillable('secret'));
    }

    public function test_make_creates_hydrated_instance(): void
    {
        $article = ArticleStub::make(['title' => 'Via make', 'body' => 'Corps']);

        $this->assertInstanceOf(ArticleStub::class, $article);
        $this->assertSame('Via make', $article->title);
    }

    public function test_get_fillable_returns_declared_list(): void
    {
        $article = new ArticleStub();
        $this->assertSame(['title', 'body', 'category_id'], $article->getFillable());
    }

    public function test_collection_creates_multiple_instances(): void
    {
        $rows = [
            ['title' => 'Article 1', 'body' => 'Corps 1'],
            ['title' => 'Article 2', 'body' => 'Corps 2'],
        ];

        $articles = ArticleStub::collection($rows);

        $this->assertCount(2, $articles);
        $this->assertSame('Article 1', $articles[0]->title);
        $this->assertSame('Article 2', $articles[1]->title);
    }
}
