FROM debian:latest

# Set the working directory to /app
WORKDIR /app

# Disable debconf prompts
ENV DEBIAN_FRONTEND=noninteractive

# Install any necessary dependencies
RUN apt-get update && \
    apt-get install -y \
        curl \
        zip \
        unzip \
        git \
        libzip-dev \
        software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    apt-get update && \
    apt-get install -y \
        php8.1 \
        php8.1-mysql \
        php8.1-zip \
        php8.1-gd \
        php8.1-curl \
        php8.1-redis \
        php8.1-mbstring \
        php8.1-xml \
        nodejs \
        redis-server \
        && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    rm -rf /var/lib/apt/lists/*

# Install npx kill-port
RUN npm install -g kill-port

# Clone the Laravel app from GitHub
RUN git clone https://github.com/Omar630603/ValidateNodeJS.git /app

# Install Laravel app dependencies
RUN composer install --no-dev --prefer-dist --no-interaction && \
    npm install && \
    npm run prod && \
    rm -rf /var/cache/apk/*

# Expose ports 9000 and 6379
EXPOSE 9000 6379

# Start php-fpm and Redis servers
CMD ["sh", "-c", "service redis-server start && php-fpm"]

# Copy files from Windows host to Docker container
COPY /public/assets/projects/api-experiment/files/.env /app/public/assets/projects/api-experiment/files/.env
COPY /public/assets/projects/auth-experiment/files/.env /app/public/assets/projects/auth-experiment/files/.env

# Run npm commands
WORKDIR /app
RUN npm install && \
    npm run prod

# Run database migrations
RUN php artisan migrate

# Seed the database
RUN php artisan db:seed

# Run Redis queue for Laravel
CMD ["sh", "-c", "service redis-server start && php artisan queue:work"]
