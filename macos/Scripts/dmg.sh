#!/bin/sh
# Release DMG: release-build the bundle, then wrap it in a versioned,
# internet-enabled disk image for the download page.
#
# Usage: ./Scripts/dmg.sh
# Output: dist/TernisLink-{VERSION}.dmg + dist/TernisLink-latest.dmg
# (both symlinks/copies of the same image; the website serves -latest).
#
# Signing: bundle.sh ad-hoc signs (local use). For distribution, run
# ./Scripts/notarize.sh afterwards (needs a Developer ID identity).
set -eu
cd "$(dirname "$0")/.."

VERSION="$(tr -d '[:space:]' < VERSION)"
./Scripts/bundle.sh --release

DMG_NAME="TernisLink-${VERSION}.dmg"
STAGING="$(mktemp -d)"
trap 'rm -rf "$STAGING"' EXIT

cp -R dist/TernisLink.app "$STAGING/"
ln -s /Applications "$STAGING/Applications"

rm -f "dist/$DMG_NAME" dist/TernisLink-latest.dmg
hdiutil create -volname "ternis.link $VERSION" \
    -srcfolder "$STAGING" -ov -format UDZO \
    -imagekey zlib-level=9 \
    "dist/$DMG_NAME" >/dev/null
cp "dist/$DMG_NAME" dist/TernisLink-latest.dmg

echo "Built (ad-hoc signed, NOT notarized — see notarize.sh):"
ls -lh "dist/$DMG_NAME" dist/TernisLink-latest.dmg
