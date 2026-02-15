# Registry

A tiny, dependency-injection-friendly in-memory registry with **per-key locks** and a **global freeze switch**.

Designed as a safer alternative to ad-hoc globals for configuration, runtime state, and bootstrapping data in PHP applications.

---

## Features

* Simple in-memory key/value storage
* **Per-key lock flags** to prevent specific mutations
* **Global freeze** to make the registry fully read-only after boot
* Clean interface for DI containers and frameworks
* Zero dependencies, tiny footprint

---

## Installation

```bash
composer require tlmcclatchey/registry
```

Requires **PHP 8.4+**.

---

## Quick Start

```php
use TLMCClatchey\Registry\MemoryRegistry;

$registry = new MemoryRegistry();

// define keys
$registry->define('config', array: true);
$registry->assign('config', 'env', 'prod');

// set scalar value
$registry->set('debug', false);

// freeze registry after boot
$registry->freeze();

// reads still work
$env = $registry->get('config')['env'];
```

After calling `freeze()`, **all mutation operations throw**.

---

## Core Concepts

### 1. Keys

A key may contain:

* scalar value (`int|string|bool|float|null`)
* array value (for map/list-style storage)

```php
$registry->set('version', '1.0.0');
$registry->define('services', array: true);
```

---

### 2. Assigning Array Values

```php
$registry->assign('services', 'cache', 'redis');
$registry->assign('services', 'queue', 'sqs');
```

Check existence:

```php
$registry->isAssigned('services', 'cache'); // true
```

---

### 3. Lock Flags

Each key can prevent specific mutations.

| Flag          | Prevents                    |
| ------------- | --------------------------- |
| `NO_SET`      | overwriting the whole value |
| `NO_APPEND`   | appending to arrays         |
| `NO_PREPEND`  | prepending to arrays        |
| `NO_ASSIGN`   | assigning subkeys           |
| `NO_UNASSIGN` | removing subkeys            |
| `NO_CLEAR`    | removing the key entirely   |

Convenience presets:

```php
RegistryLocks::READONLY
RegistryLocks::READ_MODIFY
```

Example:

```php
use TLMCClatchey\Registry\RegistryLocks;

$registry->define('config', lock: RegistryLocks::READONLY, array: true);

// any mutation now throws RegistryException
```

---

### 4. Global Freeze

Freeze the entire registry once bootstrapping is complete:

```php
$registry->freeze();
```

After freezing:

* **All mutation methods throw `RegistryException`**
* **Read operations continue working**

Perfect for:

* application boot phases
* compiled container configs
* immutable runtime state

---

## API Overview

### Read

```php
$registry->get(string $key, mixed $default = null);
$registry->has(string $key);
$registry->all();
$registry->keys();
```

### Write

```php
$registry->define(string $key, int $lock = 0, bool $array = false);
$registry->set(string $key, mixed $value, int $lock = 0);
$registry->clear(string $key);
```

### Array Operations

```php
$registry->assign(string $key, string $subkey, scalar|null $value);
$registry->unassign(string $key, string $subkey);
$registry->prepend(string $key, scalar|null $value);
$registry->append(string $key, scalar|null $value);
```

### Lifecycle

```php
$registry->freeze();
$registry->isFrozen();
```

---

## When to Use This

Good fit:

* boot configuration storage
* DI container build phase
* runtime feature flags
* small framework kernels
* CLI app state

Not intended for:

* persistence
* cross-request storage
* caching layers
* large datasets

---

## Philosophy

This library exists for one reason:

> Sometimes you *do* need global state.
> You just don’t need **chaos**.

So instead of banning globals, this gives you:

* **rules**
* **immutability after boot**
* **predictable failure**

Like a registry…
but with adult supervision.

---

## License

See the [LICENSE.md](LICENSE.md) file for full details.
