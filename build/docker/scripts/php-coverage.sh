#!/usr/bin/env bash
#
# Licensed to the Apache Software Foundation (ASF) under one
# or more contributor license agreements. See the NOTICE file
# distributed with this work for additional information
# regarding copyright ownership. The ASF licenses this file
# to you under the Apache License, Version 2.0 (the
# "License"); you may not use this file except in compliance
# with the License. You may obtain a copy of the License at
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing,
# software distributed under the License is distributed on an
# "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
# KIND, either express or implied. See the License for the
# specific language governing permissions and limitations
# under the License.
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "${ROOT_DIR}"

PHPUNIT="${PHPUNIT:-vendor/bin/phpunit}"
PHP_BIN="${PHP_BIN:-php}"
THRIFT="${THRIFT:-compiler/cpp/thrift}"
COVERAGE_DIR="${COVERAGE_DIR:-/tmp/thrift-php-coverage}"

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
  echo "php is required to run PHP coverage" >&2
  exit 1
fi

if ! "${PHP_BIN}" -r 'exit(extension_loaded("xdebug") || extension_loaded("pcov") ? 0 : 1);'; then
  echo "PHP coverage requires Xdebug or PCOV. In GitHub Actions setup-php, enable 'coverage: xdebug'." >&2
  exit 1
fi

if [ ! -x "${PHPUNIT}" ]; then
  if ! command -v composer >/dev/null 2>&1; then
    echo "composer is required to install ${PHPUNIT}" >&2
    exit 1
  fi
  composer install --no-progress --prefer-dist
fi

if [ ! -x "${THRIFT}" ]; then
  echo "Thrift compiler not found at ${THRIFT}. Build compiler/cpp/thrift or set THRIFT=/path/to/thrift." >&2
  exit 1
fi

mkdir -p \
  lib/php/test/Resources/packages/php \
  lib/php/test/Resources/packages/phpi \
  lib/php/test/Resources/packages/phpv \
  lib/php/test/Resources/packages/phpvo \
  lib/php/test/Resources/packages/phpjs \
  lib/php/test/Resources/packages/phpcm

"${THRIFT}" --gen php:nsglobal=Basic -r --out lib/php/test/Resources/packages/php lib/php/test/Resources/ThriftTest.thrift
"${THRIFT}" --gen php:inlined,nsglobal=BasicInline -r --out lib/php/test/Resources/packages/phpi lib/php/test/Resources/ThriftTest.thrift
"${THRIFT}" --gen php:validate,nsglobal=Validate -r --out lib/php/test/Resources/packages/phpv lib/php/test/Resources/ThriftTest.thrift
"${THRIFT}" --gen php:validate,oop,nsglobal=ValidateOop -r --out lib/php/test/Resources/packages/phpvo lib/php/test/Resources/ThriftTest.thrift
"${THRIFT}" --gen php:json,nsglobal=Json -r --out lib/php/test/Resources/packages/phpjs lib/php/test/Resources/ThriftTest.thrift
"${THRIFT}" --gen php:classmap,server,rest,nsglobal=Classmap -r --out lib/php/test/Resources/packages/phpcm lib/php/test/Resources/ThriftTest.thrift

rm -rf "${COVERAGE_DIR}"
mkdir -p "${COVERAGE_DIR}"

export XDEBUG_MODE="${XDEBUG_MODE:-coverage}"

"${PHP_BIN}" -d error_reporting='E_ALL & ~E_DEPRECATED' "${PHPUNIT}" \
  -c lib/php/phpunit.xml \
  --coverage-text \
  --coverage-html "${COVERAGE_DIR}/html" \
  --coverage-clover "${COVERAGE_DIR}/clover.xml" \
  "$@"

echo "PHP coverage written to ${COVERAGE_DIR}"
