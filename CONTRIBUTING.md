# Contributing to TypedArray

Thank you for helping improve TypedArray.

## Project scope

TypedArray provides small, runtime-validated collections for PHP. Keep changes focused, easy to understand, and backwards compatible wherever possible. The public API and existing behavior are important because other libraries may depend on them.

## Branches and pull requests

- `develop` is the integration branch for changes. Open pull requests against `develop`.
- `master` is the stable branch. Releases are prepared from reviewed code on `master`.
- Do not create version tags from `develop` or change the published Composer package name as part of an unrelated pull request.
- For a bug fix, explain the affected behavior, provide a minimal reproduction, and include a regression test when the test suite is available.
- For a behavior or compatibility change, document the rationale and migration impact before it is merged.

## Local setup

You will need PHP 8.0 or later and Composer.

```sh
composer install
composer validate --strict
php examples/string.php
php examples/boolean.php
php examples/class.php
```

The examples include deliberately invalid inserts and catch the resulting error. An error message at the end of an example is therefore expected.

An automated PHPUnit suite and CI checks are planned but are **not yet present** in the repository. Do not treat the example scripts as a substitute for regression tests.

## Reporting issues

Before opening an issue, check existing reports. Include the PHP version, the installed package version, a minimal reproducer, the expected behavior, and the actual behavior.

Please do not publish security-sensitive details in a public issue. Contact the maintainer privately to coordinate disclosure.

## Compatibility and releases

- Preserve PHP 8.0 compatibility while the package's Composer requirement is `>=8.0`.
- Avoid changing namespaces, class names, error semantics, or other public behavior without reviewing consumers and semantic-versioning consequences.
- Release tags belong to tested, approved commits on `master`.
- Packagist publishes package versions from Git tags; ordinary development commits are not stable releases.

This document describes the contribution policy, not a claim that all planned automation is already active.
