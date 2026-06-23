# Contributing

Contributions are welcome and will be fully credited.

We accept contributions via Pull Requests on [GitHub](https://github.com/jeffersongoncalves/laravel-service-desk).

## Pull Requests

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](https://semver.org/). Randomly breaking public APIs is not an option.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](https://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Running the checks

Before submitting a pull request, please make sure the following checks pass locally:

```bash
composer test     # Run the Pest test suite
composer analyse  # Run PHPStan static analysis
composer format   # Apply Laravel Pint code style fixes
```

## Coding Standards

This package follows the [Laravel](https://laravel.com) coding conventions and is automatically formatted with [Laravel Pint](https://laravel.com/docs/pint). Run `composer format` before committing.

**Happy coding!**
