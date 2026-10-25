<?php

namespace App\Exceptions;

/**
 * CIMA-052: the treatment status cannot be deleted because it is referenced
 * by existing admission records. Rendered by the DomainActionFailure
 * handler; the guard is staged until CIMA-060's `encounters` table exists
 * (same Schema-guarded pattern as WardService/BedService).
 */
class TreatmentStatusInUse extends DomainActionFailure {}
