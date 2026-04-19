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

cat >/etc/apt/apt.conf.d/80-retries <<'EOF'
Acquire::Retries "5";
Acquire::http::Timeout "30";
Acquire::https::Timeout "30";
EOF

apt-get update
apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg unzip

# Add ondrej/php PPA only if it isn't already configured (some base
# images ship it preinstalled, which would conflict with a second entry).
if ! grep -rqsE 'ppa\.launchpad(content)?\.net/ondrej/php' \
        /etc/apt/sources.list /etc/apt/sources.list.d; then
    install -d -m 0755 /etc/apt/keyrings
    curl -fsSL 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14AA40EC0831756756D7F66C4F4EA0AAE5267A6C' \
        | gpg --dearmor -o /etc/apt/keyrings/ondrej-php.gpg
    chmod 0644 /etc/apt/keyrings/ondrej-php.gpg

    . /etc/os-release  # provides UBUNTU_CODENAME (e.g. noble)
    echo "deb [signed-by=/etc/apt/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu ${UBUNTU_CODENAME} main" \
        > /etc/apt/sources.list.d/ondrej-php.list

    apt-get update
fi

apt-get install -y --no-install-recommends \
    php8.5-cli php8.5-curl php8.5-dom php8.5-intl \
    php8.5-mbstring php8.5-tokenizer php8.5-xml php8.5-zip

update-alternatives --set php /usr/bin/php8.5

if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- \
        --install-dir=/usr/local/bin --filename=composer
fi

php -v
composer --version

composer install --no-interaction --prefer-dist --no-progress
