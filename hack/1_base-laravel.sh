#!/bin/bash

COMPOSER_VERSION=2.4
LARAVEL_VERSION=9.3.12

########
CUR=$(realpath $(dirname $0))/CUR
TEMP_CONTAINER=temp-laravel
TEMP_IMAGE=temp-laravel:$LARAVEL_VERSION
DIRECTORY=base/laravel-$LARAVEL_VERSION

set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

COPY --from=bitnami/laravel:$LARAVEL_VERSION /opt/bitnami/laravel/ /laravel
EOF
docker build -t $IMAGE .

set -x
cd $CUR
rm -rf $DIRECTORY
docker ps -a | grep $TEMP_CONTAINER$ && docker rm -f $TEMP_CONTAINER
docker create --name=$TEMP_CONTAINER $TEMP_IMAGE
docker cp $TEMP_CONTAINER:/laravel $DIRECTORY
docker rm -f $TEMP_CONTAINER

echo
echo $DIRECTORY generated!
echo
