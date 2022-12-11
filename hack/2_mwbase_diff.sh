#!/bin/bash

cd $(dirname $0)/..

set -x
diff -r .mwbase mediawiki

