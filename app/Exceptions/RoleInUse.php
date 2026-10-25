<?php

namespace App\Exceptions;

/**
 * CIMA-041: a role cannot be deleted while it is still assigned to users.
 * Deleting would silently strip assignments; require reassignment first.
 */
class RoleInUse extends DomainActionFailure {}
