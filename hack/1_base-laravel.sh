#!/bin/bash

COMPOSER_VERSION=2.4
LARAVEL_VERSION=9.3.12

########
BASE=$(realpath $(dirname $0))/base
IMAGE=base-laravel:$LARAVEL_VERSION
CONTAINER=base-laravel-$LARAVEL_VERSION

set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

COPY --from=bitnami/laravel:$LARAVEL_VERSION /opt/bitnami/laravel/ /laravel
EOF
docker build -t $IMAGE .

set -x
cd $BASE
rm -rf laravel
docker ps -a | grep $CONTAINER$ && docker rm -f $CONTAINER
docker create --name=$CONTAINER $IMAGE
docker cp $CONTAINER:/laravel laravel-${LARAVEL_VERSION}
docker rm -f $CONTAINER

echo
echo base/laravel-${LARAVEL_VERSION} generated!
echo
