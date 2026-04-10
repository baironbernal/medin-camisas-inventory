<?php

namespace App\Exceptions;

/**
 * Represents an expected business-logic failure whose message is safe
 * to surface directly to the end user (e.g. "Stock insuficiente").
 *
 * Any other \Exception reaching the catch block is an unexpected technical
 * error whose internal message must NOT be exposed — only logged.
 */
class BusinessException extends \RuntimeException {}
