<?php

declare(strict_types=1);

namespace Bathys\Domain;

/**
 * The parent of every "this value breaks a rule" error in the domain.
 *
 * It exists so that adapters (the API, the UI) can catch one type and turn it
 * into a validation message, while each rule keeps its own named subclass.
 */
abstract class InvalidValue extends \InvalidArgumentException {}
