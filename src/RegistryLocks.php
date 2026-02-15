<?php

declare(strict_types=1);

namespace TLMcClatchey\Registry;

/**
 * Internal lock manager for MemoryRegistry.
 *
 * Stores per-key lock bitmasks and a global "frozen" flag.
 *
 * @internal
 */
final class RegistryLocks
{
    /**
     * No restrictions.
     */
    public const int READ_WRITE = 0;

    /**
     * Prevents overwriting the key via set().
     */
    public const int NO_SET = 1;

    /**
     * Prevents append().
     */
    public const int NO_APPEND = 2;

    /**
     * Prevents prepend().
     */
    public const int NO_PREPEND = 4;

    /**
     * Prevents assign().
     */
    public const int NO_ASSIGN = 8;

    /**
     * Prevents unassign().
     */
    public const int NO_UNASSIGN = 16;

    /**
     * Prevents clear().
     */
    public const int NO_CLEAR = 32;

    /**
     * Read-only preset for common cases.
     */
    public const int READONLY =
        self::NO_SET |
        self::NO_APPEND |
        self::NO_PREPEND |
        self::NO_ASSIGN |
        self::NO_UNASSIGN |
        self::NO_CLEAR;

    /**
     * Preset that allows list/map mutations, but prevents overwriting or clearing the whole key.
     */
    public const int READ_MODIFY =
        self::NO_SET |
        self::NO_CLEAR;

    /**
     * Lock bitmasks per key.
     *
     * @var array<string, int>
     */
    private array $data = [];


    /**
     * Global frozen flag. When true, all mutation is prohibited.
     */
    private bool $frozen = false;

    /**
     * Returns true if the registry is frozen.
     */
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * Sets the frozen flag.
     *
     * @param bool $frozen True to freeze (disallow mutation), false to unfreeze.
     */
    public function setFrozen(bool $frozen): void
    {
        $this->frozen = $frozen;
    }

    /**
     * Returns true if the key's lock mask includes any of the provided flags.
     *
     * If the key has no lock state, READ_WRITE is assumed.
     *
     * @param string $key Registry key.
     * @param int ...$flags One or more flags to check.
     */
    public function check(string $key, int ... $flags): bool
    {
        if (count($flags) === 0) {
            return false;
        }
        $lock = $this->data[$key] ?? self::READ_WRITE;
        // @phpstan-ignore-next-line
        return array_any($flags, fn (int $flag) => $lock & $flag);
    }

    /**
     * Sets the lock mask for a key.
     *
     * @param string $key Registry key.
     * @param int $lock Lock bitmask.
     */
    public function lock(string $key, int $lock): void
    {
        $this->data[$key] = $lock;
    }

    /**
     * Removes lock state for a key.
     *
     * @param string $key Registry key.
     */
    public function unlock(string $key): void
    {
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
        }
    }

    /**
     * Throws if the registry is frozen.
     *
     * @param string $action Calling action name (used for error messages).
     * @param string $key Registry key.
     *
     * @throws RegistryException
     */
    public function assertNotFrozen(string $action, string $key): void
    {
        if ($this->frozen) {
            throw new RegistryException("Registry action `$action` failed for $key: registry is frozen");
        }
    }

    /**
     * Throws if the key is locked against any of the provided flags.
     *
     * @param string $action Calling action name (used for error messages).
     * @param string $key Registry key.
     * @param int ...$flags One or more flags that should not be present.
     *
     * @throws RegistryException
     */
    public function assertNotLocked(string $action, string $key, int ... $flags): void
    {
        $locked = $this->check($key, ... $flags);
        if ($locked) {
            throw new RegistryException("Registry action `$action` failed for $key: value is locked for this action");
        }
    }
}
