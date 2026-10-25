<?php

namespace App\Exceptions;

/**
 * CIMA-042: an actor cannot deactivate their own account (lockout risk).
 */
class SelfDeactivation extends DomainActionFailure {}
