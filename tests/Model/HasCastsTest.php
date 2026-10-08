<?php

declare(strict_types=1);

namespace AstralOrm\Tests\Model;

use AstralOrm\Exceptions\CastException;
use AstralOrm\Model\Model;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PostStub extends Model
{
    public int    $id           = 0;
    public string $title        = '';
    public int    $views        = 0;
    public string $score        = '4.5';
    public int    $is_published = 0;
    public ?string $created_at  = '2026-01-15 10:30:00';
    public string $published_at = '2026-01-15';
    public string $meta         = '{"key":"value"}';

    protected array $casts = [
        'views'        => 'int',
        'score'        => 'float',
        'is_published' => 'bool',
        'created_at'   => 'datetime',
        'published_at' => 'date',
        'meta'         => 'array',
    ];
}

final class HasCastsTest extends TestCase
{
    public function test_cast_int(): void
    {
        $post = new PostStub();
        $post->views = 42;
        $this->assertSame(42, $post->cast('views'));
    }

    public function test_cast_float(): void
    {
        $post = new PostStub();
        $this->assertSame(4.5, $post->cast('score'));
    }

    public function test_cast_bool(): void
    {
        $post = new PostStub();
        $post->is_published = 1;
        $this->assertTrue($post->cast('is_published'));

        $post->is_published = 0;
        $this->assertFalse($post->cast('is_published'));
    }

    public function test_cast_datetime_from_string(): void
    {
        $post   = new PostStub();
        $result = $post->cast('created_at');

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-01-15', $result->format('Y-m-d'));
        $this->assertSame('10:30:00', $result->format('H:i:s'));
    }

    public function test_cast_date_has_midnight_time(): void
    {
        $post   = new PostStub();
        $result = $post->cast('published_at');

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('00:00:00', $result->format('H:i:s'));
    }

    public function test_cast_array_from_json_string(): void
    {
        $post   = new PostStub();
        $result = $post->cast('meta');

        $this->assertIsArray($result);
        $this->assertSame('value', $result['key']);
    }

    public function test_cast_returns_raw_value_when_no_cast_declared(): void
    {
        $post = new PostStub();
        $post->title = 'Test';
        $this->assertSame('Test', $post->cast('title'));
    }

    public function test_cast_returns_null_for_null_value(): void
    {
        $post = new PostStub();
        $post->created_at = null;
        $this->assertNull($post->cast('created_at'));
    }

    public function test_cast_unknown_type_throws_exception(): void
    {
        $post = new class extends Model {
            public string $foo = 'bar';
            protected array $casts = ['foo' => 'unknown_type'];
        };

        $this->expectException(CastException::class);
        $post->cast('foo');
    }

    public function test_has_cast(): void
    {
        $post = new PostStub();
        $this->assertTrue($post->hasCast('created_at'));
        $this->assertFalse($post->hasCast('title'));
    }

    public function test_cast_all_returns_all_fields(): void
    {
        $post   = new PostStub();
        $result = $post->castAll();

        $this->assertArrayHasKey('created_at', $result);
        $this->assertInstanceOf(DateTimeImmutable::class, $result['created_at']);
        $this->assertArrayHasKey('title', $result);
    }
}
