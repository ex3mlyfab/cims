<?php

namespace App\Exceptions;

/**
 * CIMA-042: the mutation would strip the system of its last active super
 * admin (ADR-002) — role removal or deactivation must be refused.
 */
class LastSuperAdmin extends DomainActionFailure {}
