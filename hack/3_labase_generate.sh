#!/bin/bash

COMPOSER_VERSION=2.4

########
APP=$(realpath $(dirname $0))/..

set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

RUN set -x \
&& cd / \
&& composer create-project laravel/laravel laravel
EOF

set -x

docker build -t labase .
cd $APP
rm -rf .labase
docker ps -a | grep labase$ && docker rm -f labase
docker create --name=labase labase
docker cp labase:/laravel .labase
docker rm -f labase
