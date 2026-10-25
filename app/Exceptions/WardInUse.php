<?php

namespace App\Exceptions;

/**
 * CIMA-044: the ward cannot be deleted (or shrunk) because installed beds
 * would be orphaned. Rendered by the DomainActionFailure handler.
 */
class WardInUse extends DomainActionFailure {}
