<?php

declare(strict_types=1);

namespace AstralOrm\Tests\Model;

use AstralOrm\Model\Model;
use PHPUnit\Framework\TestCase;

final class UserStub extends Model
{
    public int    $id         = 0;
    public string $first_name = 'Jean';
    public string $last_name  = 'Dupont';
    public string $password   = '';
    public string $slug       = '';

    // Accessor : champ calculé
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Mutator : hashage du mot de passe
    public function setPasswordAttribute(string $value): void
    {
        $this->password = 'hashed:' . $value; // simulé pour le test
    }

    // Mutator : normalisation du slug
    public function setSlugAttribute(string $value): void
    {
        $this->slug = strtolower(str_replace(' ', '-', $value));
    }
}

final class HasAccessorsTest extends TestCase
{
    public function test_get_calls_accessor_when_defined(): void
    {
        $user   = new UserStub();
        $result = $user->get('full_name');

        $this->assertSame('Jean Dupont', $result);
    }

    public function test_get_returns_property_when_no_accessor(): void
    {
        $user = new UserStub();
        $user->first_name = 'Alice';

        $this->assertSame('Alice', $user->get('first_name'));
    }

    public function test_get_returns_null_for_unknown_field(): void
    {
        $user = new UserStub();
        $this->assertNull($user->get('nonexistent'));
    }

    public function test_set_calls_mutator_when_defined(): void
    {
        $user = new UserStub();
        $user->set('password', 'plain123');

        $this->assertSame('hashed:plain123', $user->password);
    }

    public function test_set_normalizes_slug_via_mutator(): void
    {
        $user = new UserStub();
        $user->set('slug', 'Mon Super Article');

        $this->assertSame('mon-super-article', $user->slug);
    }

    public function test_set_assigns_directly_when_no_mutator(): void
    {
        $user = new UserStub();
        $user->set('first_name', 'Marie');

        $this->assertSame('Marie', $user->first_name);
    }

    public function test_set_returns_same_instance(): void
    {
        $user     = new UserStub();
        $returned = $user->set('first_name', 'Test');

        $this->assertSame($user, $returned);
    }

    public function test_has_accessor(): void
    {
        $user = new UserStub();
        $this->assertTrue($user->hasAccessor('full_name'));
        $this->assertFalse($user->hasAccessor('first_name'));
    }

    public function test_has_mutator(): void
    {
        $user = new UserStub();
        $this->assertTrue($user->hasMutator('password'));
        $this->assertFalse($user->hasMutator('first_name'));
    }
}
