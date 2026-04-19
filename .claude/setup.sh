#!/bin/bash
# Cloud environment setup script for Claude Code on the web.
# Paste the contents of this file into the "Setup script" field of your
# environment at https://claude.ai/code (Add/Edit environment).
#
# The script runs once as root on Ubuntu 24.04, then the resulting
# filesystem is snapshotted and reused for ~7 days, so subsequent
# sessions start instantly with PHP 8.5 + vendor/ already in place.

set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    software-properties-common \
    unzip

add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y --no-install-recommends \
    php8.5-cli \
    php8.5-curl \
    php8.5-dom \
    php8.5-intl \
    php8.5-mbstring \
    php8.5-tokenizer \
    php8.5-xml \
    php8.5-zip

update-alternatives --set php /usr/bin/php8.5

php -v
composer --version

composer install --no-interaction --prefer-dist --no-progress
