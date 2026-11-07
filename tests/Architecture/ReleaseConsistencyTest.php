<?php

/*
 * Release rules from docs/development.md, enforced instead of remembered.
 *
 * In indigo-nebula the changelog drifted 65 versions behind because keeping
 * it current was a checklist step. Here, a release that forgets its changelog
 * entry fails the build.
 */

function appVersion(): string
{
    $package = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/package.json'), true);

    return (string) ($package['version'] ?? '');
}

test('the app version is a plain SemVer number', function () {
    expect(appVersion())->toMatch('/^\d+\.\d+\.\d+$/');
});

test('the changelog has a dated entry for the current app version', function () {
    $changelog = (string) file_get_contents(dirname(__DIR__, 2).'/CHANGELOG.md');

    expect($changelog)->toMatch('/^## \['.preg_quote(appVersion(), '/').'\] - \d{4}-\d{2}-\d{2}$/m');
});
