#!/bin/bash

COMPOSER_VERSION=2.4
LARAVEL_VERSION=9.3.12

########
BASE=$(realpath $(dirname $0))/base

set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

COPY --from=bitnami/laravel:$LARAVEL_VERSION /opt/bitnami/laravel/ /laravel
EOF
docker build -t base-laravel .

set -x
cd $BASE
rm -rf laravel
docker ps -a | grep base-laravel$ && docker rm -f base-laravel
docker create --name=base-laravel base-laravel
docker cp base-laravel:/laravel laravel-${LARAVEL_VERSION}
docker rm -f base-laravel

echo
echo base/laravel-${LARAVEL_VERSION} generated!
echo
