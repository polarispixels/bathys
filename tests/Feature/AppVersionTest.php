<?php

use Tests\TestCase;

/*
 * The deploy check in docs/deployment.md greps this meta tag. If it ever
 * stops matching package.json, a deploy could look verified when it isn't.
 */

uses(TestCase::class);

it('publishes the package.json version in a meta tag', function () {
    $version = json_decode((string) file_get_contents(base_path('package.json')), true)['version'];

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="app-version" content="'.$version.'">', false);
});
