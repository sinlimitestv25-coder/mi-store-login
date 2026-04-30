FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpdo-mysql-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . /app/

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]
