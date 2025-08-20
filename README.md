# Health Check Bundle #

## About ##

The Health Check Bundle contains endpoint and services that can be used to check if it is up and running

## Installation ##

Require the bundle and its dependencies with composer:

```bash
$ composer require silpo-tech/health-check-bundle
```

Register the bundle:

```php
// project/config/bundles.php

return [
    HealthCheck\HealthCheckBundle::class => ['all' => true],
];
```

Routing:

```yaml
health_check:
  resource: '@HealthCheckBundle/Resources/config/routes.yaml'
```

Configuration:

```yaml
health_check:
  apps:
      # for api
      web:
        checkers:
          - doctrine_mongodb
      # for workers
      command:
        checkers:
          - doctrine_mongodb
```

Run unit tests:

```shell
docker run -it -v "$(pwd)":/project -e BASE_PATH="/project" -e DIR_SRC="/project/src/" fozzyua/docker-php-base-image:v1.0.7-php8.3.1 sh -c "APP_ENV=test composer install --working-dir=/project -o --no-interaction --ignore-platform-reqs && php /project/vendor/bin/phpunit --testsuite unit -c /project/phpunit.xml"
```

Run integration tests:

```shell
docker run -it -v "$(pwd)":/project -e BASE_PATH="/project" -e DIR_SRC="/project/src/" fozzyua/docker-php-base-image:v1.0.7-php8.3.1 sh -c "APP_ENV=test composer install --working-dir=/project -o --no-interaction --ignore-platform-reqs && php /project/vendor/bin/phpunit --testsuite integration -c /project/phpunit.xml"
```