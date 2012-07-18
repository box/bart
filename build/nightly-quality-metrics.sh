#!/usr/bin/env bash
#
# Run *all* of the quality metrics and keep track of their exit status
# If any of the jobs failed, then fail the build.
# Based on http://jenkins-php.org/
# ...http://agile.dzone.com/news/continuous-integration-php
# ...http://erichogue.ca/2011/05/php/continuous-integration-in-php/
#
# ############ Installation Notes ###########################################
# pear channel-discover pear.pdepend.org
# pear channel-discover pear.phpmd.org
# pear channel-discover components.ez.no
# # Assuming you've already installed PHPUnit, otherwise use channel-discover
# pear channel-update pear.phpunit.de
# 
# pear install pdepend/PHP_Depend-beta
# pear install --alldeps phpmd/PHP_PMD
# pear install phpunit/phpdcd-beta # dead code detector
# pear install phpunit/phpcpd # copy-paste detector
##

# Assert assumption that we're running from the project root directory
# This works for all of my projects, but someone else may want to modify this
if [[ ! -d "test" ]]; then
    echo >&2 'Could not find "test" directory. Refusing to run'
    exit 1
fi

declare statusPhpunit=0
declare statusPdepend=0
declare statusPhpmd=0
declare statusPhpcpd=0
declare statusPhpdcd=0

# Assuming you have a phpunit.xml with options for excludes, etc
phpunit \
 --log-junit build/reports/phpunit.xml \
 --coverage-clover build/reports/coverage.xml \
 test/ || statusPhpunit=$?

# Note, ignoring the pdepend.xml output for now until I get that "bug" figured out
# https://issues.jenkins-ci.org/browse/JENKINS-14196
pdepend \
 --summary-xml=./build/reports/pdepend.xml \
 --jdepend-chart=./build/reports/jdepend-chart.svg \
 --overview-pyramid=./build/reports/overview-pyramid.svg \
 --ignore=vendor \
 . || statusPdepend=$?

phpmd \
  --exclude vendor \
  --reportfile ./build/reports/phpmd.xml \
  . xml codesize,unusedcode,naming,design || statusPhpmd=$?

# copy-paste detector
phpcpd --exclude test/ --exclude vendor/ --log-pmd build/reports/phpcpd.xml .  || echo statusPhpcpd=$?

# dead code detector
phpdcd --exclude test/ --exclude vendor/ .  || statusPhpdcd=$?

set +x;
if [[ $statusPhpunit != 0 ||
      $statusPdepend != 0 ||
      $statusPhpmd != 0 ||
      $statusPhpcpd != 0 ||
      $statusPhpdcd != 0 ]]; then
   echo "



          Build failed, but not we're not returning bad exit status since that breaks quality metric publishing

          Job exit statuses:
            statusPhpunit = $statusPhpunit
            statusPdepend = $statusPdepend
            statusPhpmd = $statusPhpmd
            statusPhpcpd = $statusPhpcpd
            statusPhpdcd = $statusPhpdcd


   "
fi

