FROM php:8.3-fpm

# Instalar dependencias del sistema y locales para intl
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip locales libzip-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurar locales (opcional, pero útil para intl)
RUN locale-gen en_US.UTF-8
ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US:en
ENV LC_ALL=en_US.UTF-8

# Extensiones de PHP necesarias
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY ./src /var/www/html

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Comando por defecto
CMD ["php-fpm"]
