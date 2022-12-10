#!/bin/bash

COMPOSER_VERSION=2.4
MEDIAWIKI_VERSION=1.39.0
MWBASE_VERSION=0.1.0

########
set -euo pipefail

cd /tmp; rm -rf zeta-images; mkdir zeta-images; cd zeta-images

COMPOSER="composer --no-progress --optimize-autoloader --profile --prefer-dist --no-interaction"
MEDIAWIKI_BRANCH=$(echo "REL${MEDIAWIKI_VERSION}" | sed 's/\./_/' | sed 's/\..*//')
MEDIAWIKI_IMAGE=mediawiki:${MEDIAWIKI_VERSION}-fpm-alpine
MWBASE_IMAGE=ghcr.io/zetaoss/zeta-image:mwbase-${MWBASE_VERSION}

cat <<EOF > composer.local.json
{
    "require": {
        "illuminate/view": "8.37.0",
        "illuminate/container": "8.37.0"
    },
    "extra": {
        "merge-plugin": {
            "include": [
                "extensions/AntiSpoof/composer.json",
                "extensions/AWS/composer.json",
                "extensions/SendGrid/composer.json",
                "extensions/TemplateStyles/composer.json",
                "extensions/Widgets/composer.json",
                "extensions/Wikibase/composer.json"
            ]
        }
    }
}
EOF

cat <<EOF > Dockerfile
FROM composer:$COMPOSER_VERSION as vendor

COPY --from=$MEDIAWIKI_IMAGE /var/www/html/ /html/

RUN set -x \
&& cd /html/extensions/ \
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

COPY composer.local.json /html/

RUN set -x \
&& cd /html/ \
&& rm -f composer.lock \
&& composer install --profile --ignore-platform-reqs --no-dev

FROM debian:10
COPY --from=vendor /html/ /html/
RUN set -eux \
&& apt-get update \
&& apt-get -y install git \
&& rm -rf /var/lib/apt/lists/*
EOF

docker build -t $MWBASE_IMAGE . \
&& docker push $MWBASE_IMAGE

