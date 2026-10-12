<?php

namespace App\Services\Logistica;

/**
 * Falta algo para cerrar una entrega (foto, firma, receptor o ubicación).
 * Es un DomainException como las demás reglas de la ruta, pero aparte para
 * que la API lo conteste como 422 (dato faltante) y no como 409 (estado).
 */
class EvidenciaIncompleta extends \DomainException {}
