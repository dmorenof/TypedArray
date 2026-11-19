# Release process

Stable releases are always made from `master`. Do not tag an unreviewed commit on `develop`.

1. Merge tested changes from `develop` into `master` through a reviewed pull request.
2. Check that every required CI job passes on `master`, that the changelog describes user-facing changes, and that the PHP minimum version and public API are consistent with SemVer.
3. Select **Actions → Release → Run workflow**, choose the `master` branch, and enter a new SemVer version (without `v`, e.g. `1.1.0` for the proposed compatible update). Do not reuse a published tag.
4. The workflow runs the entire PHP test matrix and Composer validation before creating an annotated GitHub release and matching Git tag.
5. Git tags are consumed automatically by Packagist when the repository's package hook is enabled. Check Packagist separately after the release: the workflow itself does **not** upload any artifact to Composer.
6. Verify `composer require <package-name>:<version>` using an isolated consumer project.

## Controls to configure in GitHub

Enable branch protection/rulesets for `master` and `develop`: require pull requests and successful CI checks; restrict force pushes and branch deletion; consider required review. Add a protected GitHub environment called `release` and require reviewer approval for the publish job if your plan supports it.

The workflow requests write access only in the final release job; checks use read-only permissions. A manual release must still not be considered proof of publication on Packagist until the new package version appears there.

## Package identity

The package keeps the name `typedarray/typedarray`. A rename to `php-typed-array/php-typed-array` was considered and dropped: three projects depend on the existing name (`dmorenof/sdk-runtime`, `dmorenof/sdk-generator` output and `dmorenof/casafari-sdk`), and a `replace` mapping would add risk for no gain. If a rename is ever wanted, it needs a new Packagist registration, a stable release under the new name and verification with those consumers' test suites before the old package is marked abandoned. Never delete the old package or its tags.

Release numbers: additive changes are minor releases (`1.1.0` adds union types and the other additions in the changelog), so projects requiring `^1.0` receive them. Anything that breaks existing code needs a major release and a migration guide.
