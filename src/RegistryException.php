<?php

declare(strict_types=1);

namespace TLMcClatchey\Registry;

use RuntimeException;

/**
 * Base exception thrown by this library for invalid registry operations,
 * such as:
 * - attempting mutation while frozen
 * - attempting a disallowed mutation per lock flags
 * - attempting array operations on non-array values
 */
class RegistryException extends RuntimeException
{
}
