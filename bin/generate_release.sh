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
  411.conf \
  docs \
  CONTRIBUTING.md \
  LICENSE \
  README.md \
  RELEASE.md \
  bin \
  composer.json \
  composer.lock \
  config_example.php \
  db.sql \
  db_mysql.sql \
  htdocs \
  phplib \
  templates \
  tests
