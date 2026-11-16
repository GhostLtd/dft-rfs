# Image for the screenshot tool (compose.screenshots.yaml, run via bin/screenshots): FrankenPHP serves the app over
# HTTPS (staging forces SSL), and Puppeteer's Chrome drives it from the same container, since Chrome resolves
# *.localhost to its own loopback. The app is built into the image rather than bind-mounted, so a developer's
# .env.local and other local secrets can't reach it (see Dockerfile.dockerignore).
FROM node:22.23.5-bookworm-slim AS node

FROM dunglas/frankenphp:1.13.4-php8.3-bookworm

COPY --from=mlocati/php-extension-installer:2.13.2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2.11.1 /usr/bin/composer /usr/local/bin/composer

# Node (with npm and corepack, for Yarn) from the official image
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack \
    && corepack enable

# git: the ghost/* bundles are locked as Git sources only. unzip: Composer's dist archives.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions gd gmp intl pdo_mysql zip opcache \
    && echo 'memory_limit = -1' > "$PHP_INI_DIR/conf.d/zz-screenshots.ini"

# Shared by the Chrome download, the path lookup below and the runtime user (who has no home directory)
ENV PUPPETEER_CACHE_DIR=/opt/puppeteer \
    COREPACK_HOME=/opt/corepack \
    COREPACK_ENABLE_DOWNLOAD_PROMPT=0

WORKDIR /app

# Dependencies first, for cached layers. PHP before Yarn: package.json takes @ghost/govuk-frontend-bundle from vendor/.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-progress --no-scripts

COPY package.json yarn.lock .yarnrc.yml ./
RUN yarn install --immutable

COPY screenshots/package.json screenshots/yarn.lock screenshots/
# --install-deps apt-installs Chrome's shared libraries, so it needs fresh package lists
RUN cd screenshots \
    && yarn install --immutable \
    && apt-get update \
    && yarn puppeteer browsers install chrome --install-deps \
    && rm -rf /var/lib/apt/lists/* \
    && ln -s "$(node -p "require('puppeteer').executablePath()")" /usr/local/bin/google-chrome \
    && google-chrome --version \
    && chmod -R a+rX /opt/puppeteer /opt/corepack

COPY . .
RUN yarn build \
    && composer dump-autoload --optimize --no-interaction

# Fails the build if a secret-shaped file got into the image despite Dockerfile.dockerignore
RUN ! find /app \( -name '*.decrypt.private.php' -o -name '.env.local' -o -name '.env.local.php' \
        -o -name '.env.*.local' -o -name '*keys.json' \) -not -path '/app/vendor/*' -not -path '*/node_modules/*' \
    | grep .

COPY docker/screenshots/Caddyfile /etc/caddy/Caddyfile
COPY docker/screenshots/entrypoint.sh /usr/local/bin/rfs-screenshots-entrypoint

ENTRYPOINT ["rfs-screenshots-entrypoint"]
