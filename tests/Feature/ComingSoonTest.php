<?php

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class);

it('renders the coming soon page at the root', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('coming-soon'));
});

/*
 * Bathys has one user. With the site public, an open registration route
 * would let anyone create an account, so its absence is tested, not assumed.
 */
it('does not offer a registration page', function () {
    $this->get('/register')->assertNotFound();
});

it('rejects registration attempts', function () {
    $this->post('/register', [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
