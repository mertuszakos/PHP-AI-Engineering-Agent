FROM php:8.3-cli

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && apt-get install -y git \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["bash"]