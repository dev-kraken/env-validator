# Contributing

Contributions are welcome and will be fully credited.

## Pull Requests

- **[PSR-12 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-12-extended-coding-style-guide.md)** - The easiest way to apply the conventions is to use [Laravel Pint](https://github.com/laravel/pint), which is included in this project.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behavior** - Make sure the README.md and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow SemVer. Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](https://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Setup

### Composer

```bash
composer install
```

### Pest Tests

```bash
composer test
```

### PHPStan

```bash
composer analyse
```

### Laravel Pint

```bash
# Check code style
composer cs

# Fix code style issues
composer cs:fix
```
