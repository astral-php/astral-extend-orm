<?php

declare(strict_types=1);

namespace AstralOrm\Tests\Model;

use AstralOrm\Model\Model;
use PHPUnit\Framework\TestCase;

final class UserHiddenStub extends Model
{
    public int    $id             = 1;
    public string $name           = 'Alice';
    public string $email          = 'alice@example.com';
    public string $password       = 'hashed_password';
    public string $remember_token = 'tok_abc';

    protected array $hidden = ['password', 'remember_token'];
}

final class UserVisibleStub extends Model
{
    public int    $id             = 1;
    public string $name           = 'Bob';
    public string $email          = 'bob@example.com';
    public string $password       = 'hashed';
    public string $internal_note  = 'note interne';

    protected array $visible = ['id', 'name', 'email'];
}

final class HasHiddenTest extends TestCase
{
    public function test_to_array_excludes_hidden_fields(): void
    {
        $user  = new UserHiddenStub();
        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_to_array_with_visible_includes_only_visible_fields(): void
    {
        $user  = new UserVisibleStub();
        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('internal_note', $array);
    }

    public function test_to_array_with_extra_except_fields(): void
    {
        $user  = new UserHiddenStub();
        $array = $user->toArray(['email']); // exclure email en plus

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
    }

    public function test_to_json_returns_valid_json(): void
    {
        $user = new UserHiddenStub();
        $json = $user->toJson();

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey('password', $decoded);
    }

    public function test_with_hidden_returns_all_fields(): void
    {
        $user  = new UserHiddenStub();
        $array = $user->withHidden();

        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('remember_token', $array);
    }

    public function test_to_array_does_not_include_trait_internal_properties(): void
    {
        $user  = new UserHiddenStub();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('fillable', $array);
        $this->assertArrayNotHasKey('casts', $array);
        $this->assertArrayNotHasKey('hidden', $array);
        $this->assertArrayNotHasKey('visible', $array);
    }
}
