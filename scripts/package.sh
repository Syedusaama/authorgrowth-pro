#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/authorgrowth-pro-0.3.0.zip
zip -qr dist/authorgrowth-pro-0.3.0.zip authorgrowth-pro
unzip -t dist/authorgrowth-pro-0.3.0.zip
