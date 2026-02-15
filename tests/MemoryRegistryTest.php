<?php

declare(strict_types=1);

namespace TLMcClatchey\RegistryTests;

use PHPUnit\Framework\TestCase;
use TLMcClatchey\Registry\MemoryRegistry;
use TLMcClatchey\Registry\RegistryException;
use TLMcClatchey\Registry\RegistryLocks;

final class MemoryRegistryTest extends TestCase
{
    public function testSetDefinesKeyWhenNotPresent(): void
    {
        $r = new MemoryRegistry();

        $this->assertFalse($r->has('foo'));
        $r->set('foo', 'bar');
        $this->assertTrue($r->has('foo'));
        $this->assertSame('bar', $r->get('foo'));
    }

    public function testDefineInitializesNullOrEmptyArray(): void
    {
        $r = new MemoryRegistry();

        $r->define('a'); // default: null
        $this->assertTrue($r->has('a'));
        $this->assertNull($r->get('a'));

        $r->define('b', array: true);
        $this->assertSame([], $r->get('b'));
    }

    public function testAssignAndIsAssignedAndUnassign(): void
    {
        $r = new MemoryRegistry();

        $r->define('map', array: true);
        $this->assertFalse($r->isAssigned('map', 'x'));

        $r->assign('map', 'x', 123);
        $this->assertTrue($r->isAssigned('map', 'x'));
        $this->assertSame(['x' => 123], $r->get('map'));

        $r->unassign('map', 'x');
        $this->assertFalse($r->isAssigned('map', 'x'));
        $this->assertSame([], $r->get('map'));
    }

    public function testAppendAndPrepend(): void
    {
        $r = new MemoryRegistry();

        $r->define('list', array: true);
        $r->append('list', 'b');
        $r->prepend('list', 'a');
        $r->append('list', 'c');

        $this->assertSame(['a', 'b', 'c'], $r->get('list'));
    }

    public function testArrayOperationsRequireDefinedArrayKey(): void
    {
        $r = new MemoryRegistry();

        // not defined
        $this->expectException(RegistryException::class);
        $r->assign('missing', 'x', 1);
    }

    public function testArrayOperationsRejectNonArrayValue(): void
    {
        $r = new MemoryRegistry();
        $r->set('scalar', 'nope');

        $this->expectException(RegistryException::class);
        $r->append('scalar', 'x');
    }

    public function testFreezeBlocksAllMutations(): void
    {
        $r = new MemoryRegistry();

        $r->set('foo', 'bar');
        $r->define('arr', array: true);
        $r->freeze();

        $this->assertTrue($r->isFrozen());

        // set blocked
        try {
            $r->set('foo', 'baz');
            $this->fail('Expected RegistryException when calling set() after freeze().');
        } catch (RegistryException) {
            $this->assertSame('bar', $r->get('foo'));
        }

        // define blocked
        $this->expectException(RegistryException::class);
        $r->define('newkey');
    }

    public function testFreezeBlocksArrayMutations(): void
    {
        $r = new MemoryRegistry();
        $r->define('arr', array: true);
        $r->freeze();

        $this->expectException(RegistryException::class);
        $r->assign('arr', 'x', 1);
    }

    public function testLocksPreventSpecificMutations(): void
    {
        $r = new MemoryRegistry();

        // NO_SET prevents set() once defined
        $r->define('k1', lock: RegistryLocks::NO_SET);
        $this->expectException(RegistryException::class);
        $r->set('k1', 'value');
    }

    public function testNoAssignPreventsAssign(): void
    {
        $r = new MemoryRegistry();

        $r->define('map', lock: RegistryLocks::NO_ASSIGN, array: true);

        $this->expectException(RegistryException::class);
        $r->assign('map', 'x', 1);
    }

    public function testNoUnassignPreventsUnassign(): void
    {
        $r = new MemoryRegistry();

        $r->define('map', lock: RegistryLocks::NO_UNASSIGN, array: true);
        // assign is allowed here, since only NO_UNASSIGN is set
        $r->assign('map', 'x', 1);

        $this->expectException(RegistryException::class);
        $r->unassign('map', 'x');
    }

    public function testNoAppendAndNoPrepend(): void
    {
        $r = new MemoryRegistry();

        $r->define('list', lock: RegistryLocks::NO_APPEND | RegistryLocks::NO_PREPEND, array: true);

        $this->expectException(RegistryException::class);
        $r->append('list', 1);
    }

    public function testClearRemovesValueAndUnlocksKey(): void
    {
        $r = new MemoryRegistry();

        // Define with NO_SET so set() would normally be blocked.
        $r->define('locked', lock: RegistryLocks::NO_SET);
        $this->assertTrue($r->has('locked'));

        // Clearing should remove the key AND its lock state.
        $r->clear('locked');
        $this->assertFalse($r->has('locked'));

        // Now set() should work again (it will define + set).
        $r->set('locked', 'ok');
        $this->assertSame('ok', $r->get('locked'));
    }

    public function testClearHonorsNoClearLock(): void
    {
        $r = new MemoryRegistry();

        $r->define('protected', lock: RegistryLocks::NO_CLEAR);
        $this->assertTrue($r->has('protected'));

        $this->expectException(RegistryException::class);
        $r->clear('protected');
    }

    public function testGetDefaultWhenMissing(): void
    {
        $r = new MemoryRegistry();

        $this->assertSame('fallback', $r->get('missing', 'fallback'));
        $this->assertNull($r->get('missing'));
    }

    public function testKeysAndAll(): void
    {
        $r = new MemoryRegistry();

        $r->set('a', 1);
        $r->set('b', 2);

        $keys = $r->keys();
        sort($keys);

        $this->assertSame(['a', 'b'], $keys);
        $this->assertSame(['a' => 1, 'b' => 2], $r->all());
    }
}
