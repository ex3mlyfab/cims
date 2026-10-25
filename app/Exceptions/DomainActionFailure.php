<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A domain-expected failure rendered to the user as a friendly redirect-back
 * toast (web) or 422 (JSON) instead of a 500 (cima-architecture exception
 * mapping). Subclasses: RoleInUse, LastSuperAdmin, ...
 */
abstract class DomainActionFailure extends RuntimeException {}
