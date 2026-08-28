<?php

namespace App\Exceptions;

use RuntimeException;

class TransactionMutationBlocked extends RuntimeException
{
    // Domain-safe mutation could not be proven. The caller should show the
    // exception message to the user without recording a successful audit.
}
