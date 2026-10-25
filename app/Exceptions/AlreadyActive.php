<?php

namespace App\Exceptions;

/**
 * CIMA-042: redundant lifecycle action (e.g. reactivating an active account).
 */
class AlreadyActive extends DomainActionFailure {}
