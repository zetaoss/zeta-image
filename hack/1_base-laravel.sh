#!/bin/bash

COMPOSER_VERSION=2.4
LARAVEL_VERSION=9.3.12

########
BASE=$(realpath $(dirname $0))/../base

set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

COPY --from=bitnami/laravel:$LARAVEL_VERSION /opt/bitnami/laravel/ /laravel
EOF
docker build -t larabase .

set -x
cd $BASE
rm -rf laravel
docker ps -a | grep larabase$ && docker rm -f larabase
docker create --name=larabase larabase
docker cp larabase:/laravel laravel
docker rm -f larabase

