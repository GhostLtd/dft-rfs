#!/bin/sh
# Installs dependencies, then runs the MySQL test suite. Arguments are appended to phpunit,
# e.g. `bin/test-mysql --filter ViewsTest`.
set -eu

# flock serialises installs from overlapping runs in the same checkout; vendor/ may not exist yet
mkdir -p vendor build/test-results
# --no-scripts: auto-scripts would clear the cache (tests/bootstrap.php does that) and write public/bundles
flock vendor/.install.lock composer install --no-interaction --no-progress --no-scripts

exec vendor/bin/phpunit --testsuite mysql --log-junit build/test-results/mysql.xml "$@"
