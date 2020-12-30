# Contributing

Thank you for considering a contribution to our project! We welcome all
fixes and improvements! Before contributing, please review our [code of
conduct][coc].

Alright, to get going:

1. Fork this repository.
1. Create your feature branch (`git checkout -b my-new-feature`).
1. Commit your changes (see Conventional Commits below).
1. Push to the branch (`git push origin my-new-feature`).
1. Create new Pull Request.

[coc]: ./CODE_OF_CONDUCT.md

## Targeting earlier versions

Feature branches made off of `master` target PHP 8. To target earlier
releases and versions of PHP, checkout the next branch for that series:

```sh
# to target changes for the next release in the 1.x (PHP 5) series:
$ git checkout -b fix-something-in-v1 next/1.x

# to target changes for the next release in the 2.x (PHP 7) series:
$ git checkout -b add-something-in-v2 next/2.x

# to target changes for the next release in the 3.x (PHP 8) series:
$ git checkout -b whiz-bang master
```

### Fix and Feature Propagation

Bug fixes should go into the lowest affected version, and then be patched
into successively higher versions. For example, if a bug is found in version
1.9.0; and it's found to exist in version 2.2.10, but not master; then it
should be patched in `next/1.x` and `next/2.x` but not `master`. Likewise,
if a bug is found in 2.2.10; but not 1.9.0 or master; then it should go into
`next/2.x` only.

Similarly, features propagate forward using the same process: patch them in
the lowest version accepting features (currently `next/2.x`) and forward
(currently to `master`).

## Conventional Commits

This repository follows the [Conventional Commits][cc] guidelines. Go read
that then, when making commits, ensure that your commit messages conform.

Example conforming commit message:

```
feat: add more foo to the bar

Fixes #123

BREAKING CHANGE: more foo in bar breaks baz
```

Optionally, you can use commitizen to format your commit messages.

```sh
$ npm install -g commitizen
$ npm install -g cz-conventional-changelog
$ echo '{ "path": "cz-conventional-changelog" }' >> ~/.czrc
```

And then commit using `git cz`. You'll be prompted to describe your commit.

[cc]: https://www.conventionalcommits.org

# Testing

Tests are in `tests/`.

To run the tests: `composer test`

To fix code style issues: `composer fix`

# Tagging

1. `ROLLBAR_PHP_TAG=[version number]`
1. `git checkout master`
1. Update version numbers in `src/Payload/Notifier.php` and `tests/NotifierTest.php`.
1. `git add .`
1. `git commit -m"Bump version numbers"`.
1. `git push origin master`
1. `git tag v$ROLLBAR_PHP_TAG`
1. `git push --tags`

# Need help?

Ask in our [Discussion Q&amp;A][q-a]

[q-a]: https://github.com/rollbar/rollbar-php/discussions/categories/q-a
