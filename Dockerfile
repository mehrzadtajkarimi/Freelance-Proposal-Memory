FROM php:8.4-fpm-bookworm

ARG USER_ID=1000
ARG GROUP_ID=1000

ENV TZ=UTC

COPY --from=node:22-bookworm-slim /usr/local/ /usr/local/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        supervisor \
        tzdata \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && ln -snf "/usr/share/zoneinfo/${TZ}" /etc/localtime \
    && echo "${TZ}" > /etc/timezone \
    && groupadd --gid "${GROUP_ID}" appuser \
    && useradd --uid "${USER_ID}" --gid appuser --create-home --shell /bin/bash appuser \
    && mkdir -p /var/www/html /var/log/supervisor \
    && chown -R appuser:appuser /var/www/html \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i \
        -e '/^user = www-data$/d' \
        -e '/^group = www-data$/d' \
        -e 's/^;catch_workers_output = yes/catch_workers_output = yes/' \
        /usr/local/etc/php-fpm.d/www.conf \
    && git config --system --add safe.directory /var/www/html

COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/php/local.ini /usr/local/etc/php/conf.d/99-local.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/app-entrypoint.sh /usr/local/bin/app-entrypoint

RUN chmod 0755 /usr/local/bin/app-entrypoint

WORKDIR /var/www/html
USER appuser

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
