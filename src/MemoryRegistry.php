<?php

declare(strict_types=1);

namespace TLMcClatchey\Registry;

use TLMcClatchey\Registry\Contracts\RegistryInterface;

/**
 * In-memory registry implementation.
 *
 * Stores values in a local array and enforces:
 * - global mutation freeze (once frozen, all writes fail)
 * - per-key lock flags (deny certain operations per key)
 */
final class MemoryRegistry implements RegistryInterface
{
    /**
     * Stored registry values.
     *
     * @var array<string, array<string, array<int|string, int|string|bool|float|null>|int|string|bool|float|null>|int|string|bool|float|null>
     */
    private array $values = [];

    /**
     * Per-key lock state and frozen flag.
     */
    private RegistryLocks $locks;

    public function __construct()
    {
        $this->locks = new RegistryLocks();
    }

    /**
     * Validates that a key exists and references an array value.
     *
     * @param string $action Name of the calling action (used for error messages).
     * @param string $key Registry key.
     *
     * @throws RegistryException If the key is not defined or does not reference an array.
     */
    private function validateArrayOperation(string $action, string $key): void
    {
        if (!$this->has($key)) {
            throw new RegistryException("Registry action `$action` failed for $key: is not defined");
        }
        if (!is_array($this->values[$key])) {
            throw new RegistryException("Registry action `$action` failed for $key: defined value must be an array.");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isAssigned(string $key, string $subkey): bool
    {
        if (!$this->has($key) || !is_array($this->values[$key])) {
            return false;
        }
        return array_key_exists($subkey, $this->values[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function isFrozen(): bool
    {
        return $this->locks->isFrozen();
    }

    /**
     * {@inheritDoc}
     */
    public function freeze(): void
    {
        $this->locks->setFrozen(true);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, float|int|bool|array|string|null $default = null): array|int|string|bool|float|null
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * {@inheritDoc}
     */
    public function keys(): array
    {
        return array_keys($this->values);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $key): RegistryInterface
    {
        $this->locks->assertNotFrozen(__FUNCTION__, $key);
        if ($this->has($key)) {
            $this->locks->assertNotLocked(__FUNCTION__, $key, RegistryLocks::NO_CLEAR);
            unset($this->values[$key]);
            $this->locks->unlock($key);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function define(string $key, int $lock = RegistryLocks::READ_WRITE, bool $array = false): RegistryInterface
    {
        $this->locks->assertNotFrozen(__FUNCTION__, $key);
        if ($this->has($key)) {
            throw new RegistryException("Registry action `define` failed for $key: is already defined");
        }
        $this->values[$key] = $array ? [] : null;
        $this->locks->lock($key, $lock);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, float|int|bool|array|string|null $value, int $lock = RegistryLocks::READ_WRITE): RegistryInterface
    {
        if (!$this->has($key)) {
            $this->define($key, $lock, is_array($value));
        } else {
            $this->locks->assertNotFrozen(__FUNCTION__, $key);
            $this->locks->assertNotLocked(__FUNCTION__, $key, RegistryLocks::NO_SET);
        }
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function assign(string $key, string $subkey, float|bool|int|string|null $value): RegistryInterface
    {
        $this->locks->assertNotFrozen(__FUNCTION__, $key);
        $this->validateArrayOperation(__FUNCTION__, $key);
        $this->locks->assertNotLocked(__FUNCTION__, $key, RegistryLocks::NO_ASSIGN);
        if (!is_array($this->values[$key])) {
            throw new RegistryException("Registry action `assign` failed for $key: value must be an array.");
        }
        $this->values[$key][$subkey] = $value;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function unassign(string $key, string $subkey): RegistryInterface
    {
        $this->locks->assertNotFrozen(__FUNCTION__, $key);
        $this->validateArrayOperation(__FUNCTION__, $key);
        $this->locks->assertNotLocked(__FUNCTION__, $key, RegistryLocks::NO_UNASSIGN);
        if ($this->isAssigned($key, $subkey)) {
            unset($this->values[$key][$subkey]);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(string $key, float|bool|int|string|null $value): RegistryInterface
    {
        $this->locks->assertNotFrozen(__FUNCTION__, $key);
        $this->validateArrayOperation(__FUNCTION__, $key);
        $this->locks->assertNotLocked(__FUNCTION__, $key, RegistryLocks::NO_PREPEND);
        // @phpstan-ignore-next-line
        $this->values[$key] = array_merge([$value], $this->values[$key]);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function append(string $key, float|bool|int|string|null $value): RegistryInterface
    {
        $this->locks->assertNotFrozen(__FUNCTION__, $key);
        $this->validateArrayOperation(__FUNCTION__, $key);
        $this->locks->assertNotLocked(__FUNCTION__, $key, RegistryLocks::NO_APPEND);
        // @phpstan-ignore-next-line
        $this->values[$key] = array_merge($this->values[$key], [$value]);
        return $this;
    }
}
