<?php

/*
 * The dependency rule from ADR-0007, made executable.
 *
 * Documentation can be ignored; a failing build cannot. These rules keep the
 * core independent of the framework, which is what makes it testable in
 * milliseconds and readable on its own.
 */

arch('the core never depends on the framework or the app layer')
    ->expect('Bathys')
    ->not->toUse(['Illuminate', 'Laravel', 'Inertia', 'App']);

arch('the domain depends on nothing outside itself')
    ->expect('Bathys\Domain')
    ->not->toUse(['Bathys\Application', 'Bathys\Ports']);

arch('ports depend on nothing in the application layer')
    ->expect('Bathys\Ports')
    ->not->toUse('Bathys\Application');

arch('the core declares strict types')
    ->expect('Bathys')
    ->toUseStrictTypes();
