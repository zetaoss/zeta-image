#!/bin/bash

COMPOSER_VERSION=2.4
MEDIAWIKI_VERSION=1.39.0

########
CUR=$(realpath $(dirname $0))/CUR
TEMP_CONTAINER=temp-mediawiki
TEMP_IMAGE=temp-mediawiki:$MEDIAWIKI_VERSION
DIRECTORY=base/mediawiki-$MEDIAWIKI_VERSION

set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

MEDIAWIKI_BRANCH=$(echo "REL${MEDIAWIKI_VERSION}" | sed 's/\./_/' | sed 's/\..*//')
MEDIAWIKI_IMAGE=mediawiki:${MEDIAWIKI_VERSION}-fpm-alpine

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

COPY --from=$MEDIAWIKI_IMAGE /var/www/html/ /mediawiki/

RUN set -x \
&& cd /mediawiki/extensions/ \
&& git clone --depth=1 -b v0.11.1 https://github.com/edwardspec/mediawiki-aws-s3.git AWS -c advice.detachedHead=false \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/AntiSpoof.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/CheckUser.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/CharInsert.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/intersection.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/OAuth.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/Score.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/SendGrid.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/TemplateStyles.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/UserMerge.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/Widgets.git \
&& git clone --depth=1 -b $MEDIAWIKI_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/Wikibase.git && cd Wikibase && git submodule update --init --recursive

RUN set -x \
&& cd /mediawiki/ \
&& rm -f composer.lock \
&& mv composer.local.json-sample composer.local.json \
&& composer install --profile --ignore-platform-reqs --no-dev

RUN set -x \
&& cd /mediawiki/ \
&& find -name .git -exec rm -rf {} +
EOF
docker build -t $IMAGE .

set -x
cd $BASE
rm -rf $DIRECTORY
docker ps -a | grep $TEMP_CONTAINER$ && docker rm -f $TEMP_CONTAINER
docker create --name=$TEMP_CONTAINER $TEMP_IMAGE
docker cp $TEMP_CONTAINER:/mediawiki $DIRECTORY
docker rm -f $TEMP_CONTAINER

echo
echo $DIRECTORY generated!
echo
