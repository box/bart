#!/usr/bin/env sh
#
# Small script to run a jenkins post-commit build
##

set +x

# Set our default phpunit flags
PHPUNIT_FLAGS="--log-junit build/reports/phpunit.xml $PHPUNIT_FLAGS"

# Assert assumption that we're running from the project root directory
# This works for all of my projects, but someone else may want to modify this
if [[ ! -d "test" ]]; then
    echo >&2 'Could not find "test" directory. Refusing to run'
    exit 1
fi

echo "

$(git show --name-status)

"

set -x

# PHPUnit doesn't have a --no-colors option
# For jenkins, check out https://wiki.jenkins-ci.org/display/JENKINS/AnsiColor+Plugin
phpunit \
  $PHPUNIT_FLAGS \
  test/

