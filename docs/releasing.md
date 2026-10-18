# Release process

Stable releases are always made from `master`. Do not tag an unreviewed commit on `develop`.

1. Merge tested changes from `develop` into `master` through a reviewed pull request.
2. Check that every required CI job passes on `master`, that the changelog describes user-facing changes, and that the PHP minimum version and public API are consistent with SemVer.
3. Select **Actions → Release → Run workflow**, choose the `master` branch, and enter a new SemVer version (without `v`, e.g. `1.0.3`). Do not reuse a published tag.
4. The workflow runs the entire PHP test matrix and Composer validation before creating an annotated GitHub release and matching Git tag.
5. Git tags are consumed automatically by Packagist when the repository's package hook is enabled. Check Packagist separately after the release: the workflow itself does **not** upload any artifact to Composer.
6. Verify `composer require <package-name>:<version>` using an isolated consumer project.

## Controls to configure in GitHub

Enable branch protection/rulesets for `master` and `develop`: require pull requests and successful CI checks; restrict force pushes and branch deletion; consider required review. Add a protected GitHub environment called `release` and require reviewer approval for the publish job if your plan supports it.

The workflow requests write access only in the final release job; checks use read-only permissions. A manual release must still not be considered proof of publication on Packagist until the new package version appears there.

## Proposed package rename

The preferred future Composer package name is `php-typed-array/php-typed-array` and the preferred repository URL is `dmorenof/php-typed-array`. This requires confirming availability, changing `composer.json` in the appropriate release, registering a new Packagist package, and marking the old package as abandoned with a replacement pointer. **Do not** delete the old package or old tags, as existing consumers may still depend on them.
