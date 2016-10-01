#!/bin/bash

npm update && \
bower update && \
bin/composer.phar update && \
grunt prod && \
tar cvzf release.tgz \
  --exclude-vcs \
  --exclude bin/composer.phar \
  --exclude bin/generate_release.sh \
  --exclude htdocs/index-src.html \
  --exclude htdocs/assets \
  docs \
  CONTRIBUTING.md \
  LICENSE \
  README.md \
  RELEASE.md \
  bin \
  composer.json \
  composer.lock \
  config.php \
  db.sql \
  htdocs \
  phplib \
  templates \
  tests
