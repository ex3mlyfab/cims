<?php

namespace App\Exceptions;

/**
 * CIMA-045: the bed cannot be deleted (or would be invalid) because of its
 * current use — allocated/occupied beds are protected. Rendered by the
 * DomainActionFailure handler.
 */
class BedInUse extends DomainActionFailure {}
