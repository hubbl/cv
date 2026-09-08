#!/bin/sh
set -eu
umask 022
cp /release.tar.gz /output/release.tar.gz.tmp
sha256sum /output/release.tar.gz.tmp | cut -d ' ' -f 1 > /output/release.tar.gz.sha256.tmp
mv /output/release.tar.gz.tmp /output/release.tar.gz
mv /output/release.tar.gz.sha256.tmp /output/release.tar.gz.sha256
echo 'Production artifact exported to .deploy/release.tar.gz (no server connection).'
