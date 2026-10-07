<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a transfer or dispatch that already moved stock cannot be cancelled,
 * for example because the period is closed or the destination no longer has the stock.
 * The message is safe to show to the user.
 */
class InventoryReversalException extends RuntimeException {}
