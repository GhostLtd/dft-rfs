# PHP image for the MySQL test suite (compose.mysql-tests.yaml, run via bin/test-mysql).
# Matches production's App Engine php83 runtime. The repo is bind-mounted at /app at runtime.
FROM php:8.5.12-cli-bookworm

COPY --from=mlocati/php-extension-installer:2.13.2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2.11.1 /usr/bin/composer /usr/local/bin/composer

# git: the ghost/* bundles are locked as Git sources only (no dist archive), so a clean install clones them.
# unzip: lets Composer extract every other package's dist archive natively.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions gd gmp intl pdo_mysql zip \
    && echo 'memory_limit = -1' > "$PHP_INI_DIR/conf.d/zz-tests.ini"

# Fail the build, not the tests, if the image can't install the locked dependencies.
# (check-platform-reqs only checks PHP and its extensions, hence the separate git check.)
COPY composer.json composer.lock /tmp/platform-check/
RUN cd /tmp/platform-check \
    && composer check-platform-reqs --lock \
    && git --version \
    && rm -rf /tmp/platform-check

COPY docker/test-php/entrypoint.sh /usr/local/bin/rfs-test-entrypoint

WORKDIR /app
ENTRYPOINT ["rfs-test-entrypoint"]
