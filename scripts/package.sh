#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/authorgrowth-pro-0.4.0-rc.2.zip
zip -qr dist/authorgrowth-pro-0.4.0-rc.2.zip authorgrowth-pro
unzip -t dist/authorgrowth-pro-0.4.0-rc.2.zip
sha256sum dist/authorgrowth-pro-0.4.0-rc.2.zip > dist/authorgrowth-pro-0.4.0-rc.2.zip.sha256
