#!/bin/sh
# Draait alle tests: unit (crypto, TOTP, router, opslag) en end-to-end (echte HTTP-verzoeken).
# End-to-end gebruikt een tijdelijke database en de ingebouwde PHP-server; poort wordt willekeurig gekozen.
set -e
cd "$(dirname "$0")/.."
php tests/unit.php
php tests/e2e.php
