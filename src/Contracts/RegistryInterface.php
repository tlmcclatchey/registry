<?php

declare(strict_types=1);

namespace TLMcClatchey\Registry\Contracts;

use TLMcClatchey\Registry\RegistryException;
use TLMcClatchey\Registry\RegistryLocks;

/**
 * Contract for a small in-memory registry intended to act as a controlled,
 * DI-friendly alternative to ad-hoc globals.
 *
 * Keys may be defined and optionally locked with per-key flags. The entire
 * registry can be "frozen" to prohibit all subsequent mutation.
 */
interface RegistryInterface
{
    /**
     * Returns true if the given subkey exists under the given key.
     *
     * This only applies when the stored value at $key is an array.
     *
     * @param string $key Registry key.
     * @param string $subkey Array subkey to check.
     */
    public function isAssigned(string $key, string $subkey): bool;

    /**
     * Returns true if the registry has been frozen (mutations prohibited).
     */
    public function isFrozen(): bool;

    /**
     * Freezes the registry, preventing any future mutation operations.
     *
     * This is intended for "boot complete" scenarios where configuration/state
     * should become read-only for the remainder of the request/process.
     */
    public function freeze(): void;

    /**
     * Returns the value for a key, or $default if it is not present.
     *
     * @param string $key Registry key.
     * @param array<string, array<int|string, int|string|bool|float|null>|int|string|bool|float|null>|int|string|bool|float|null $default Default if key is not present.
     *
     * @return array<string, array<int|string, int|string|bool|float|null>|int|string|bool|float|null>|int|string|bool|float|null
     */
    public function get(string $key, array|int|string|bool|float|null $default = null): array|int|string|bool|float|null;

    /**
     * Returns true if the key exists in the registry (even if its value is null).
     *
     * @param string $key Registry key.
     */
    public function has(string $key): bool;

    /**
     * Returns all registry values.
     *
     * @return array<string, array<string, array<int|string, int|string|bool|float|null>|int|string|bool|float|null>|int|string|bool|float|null>
     */
    public function all(): array;

    /**
     * Returns all defined registry keys.
     *
     * @return list<string>
     */
    public function keys(): array;

    /**
     * Clears a key (removes it entirely) and removes its lock state.
     *
     * If the key does not exist, this is a no-op.
     *
     * @param string $key Registry key.
     *
     * @throws RegistryException If registry is frozen or key is locked against clearing.
     */
    public function clear(string $key): RegistryInterface;

    /**
     * Defines a key with an optional lock and optional initial array value.
     *
     * If $array is true, the initial value will be an empty array. Otherwise, it
     * will be null.
     *
     * @param string $key Registry key.
     * @param int $lock Lock bitmask (see RegistryLocks::*).
     * @param bool $array Whether to initialize the key as an empty array.
     *
     * @throws RegistryException If registry is frozen or key already exists.
     */
    public function define(string $key, int $lock = RegistryLocks::READ_WRITE, bool $array = false): RegistryInterface;

    /**
     * Sets a key to a value, optionally defining it if it does not already exist.
     *
     * If the key does not exist, it will be defined using the provided $lock.
     * If the value is an array, the key is initialized as an array.
     *
     * @param string $key Registry key.
     * @param array<string, array<int|string, int|string|bool|float|null>|int|string|bool|float|null>|int|string|bool|float|null $value Value to set.
     * @param int $lock Lock bitmask if the key is being defined (see RegistryLocks::*).
     *
     * @throws RegistryException If registry is frozen or key is locked against set.
     */
    public function set(string $key, array|int|string|bool|float|null $value, int $lock = RegistryLocks::READ_WRITE): RegistryInterface;

    /**
     * Assigns a scalar value into an array stored at $key under $subkey.
     *
     * @param string $key Registry key that must reference an array.
     * @param string $subkey Array key.
     * @param int|string|bool|float|null $value Value to assign.
     *
     * @throws RegistryException If registry is frozen, key is not defined, value is not an array, or key is locked.
     */
    public function assign(string $key, string $subkey, int|string|bool|float|null $value): RegistryInterface;

    /**
     * Unassigns a subkey from an array stored at $key.
     *
     * If the subkey is not assigned, this is a no-op.
     *
     * @param string $key Registry key that must reference an array.
     * @param string $subkey Array key to remove.
     *
     * @throws RegistryException If registry is frozen, key is not defined, value is not an array, or key is locked.
     */
    public function unassign(string $key, string $subkey): RegistryInterface;

    /**
     * Prepends a scalar value onto an array stored at $key.
     *
     * @param string $key Registry key that must reference an array.
     * @param int|string|bool|float|null $value Value to prepend.
     *
     * @throws RegistryException If registry is frozen, key is not defined, value is not an array, or key is locked.
     */
    public function prepend(string $key, int|string|bool|float|null $value): RegistryInterface;

    /**
     * Appends a scalar value onto an array stored at $key.
     *
     * @param string $key Registry key that must reference an array.
     * @param int|string|bool|float|null $value Value to append.
     *
     * @throws RegistryException If registry is frozen, key is not defined, value is not an array, or key is locked.
     */
    public function append(string $key, int|string|bool|float|null $value): RegistryInterface;
}
