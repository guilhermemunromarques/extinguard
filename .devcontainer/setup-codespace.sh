#!/bin/bash

set -e

if [ "$CODESPACES" = "true" ]; then
    APP_URL="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"

    if grep -q "^APP_URL=" .env; then
        sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
    else
        echo "APP_URL=${APP_URL}" >> .env
    fi

    echo "==> Codespaces APP_URL: ${APP_URL}"
fi
