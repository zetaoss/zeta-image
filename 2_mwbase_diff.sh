#!/bin/bash

cd $(dirname $0)/app/

set -x
diff -r .mwbase mediawiki

