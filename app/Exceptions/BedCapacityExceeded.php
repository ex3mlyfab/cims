<?php

namespace App\Exceptions;

/**
 * CIMA-045: the ward's declared capacity cannot absorb another bed.
 * Rendered by the DomainActionFailure handler.
 */
class BedCapacityExceeded extends DomainActionFailure {}
