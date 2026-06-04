# Internals

## Unit testing

Run a driver-specific test suite through Docker:

```shell
make test-sqlite
make test-mysql
make test-pgsql
make test-mssql
make test-oracle
```

Run all configured driver suites:

```shell
make test-all
```

## Static analysis

```shell
make psalm
```

## Code style

```shell
make cs-fixer
```
