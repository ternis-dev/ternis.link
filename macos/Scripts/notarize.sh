#!/bin/sh
# Distribution signing + notarization for the release DMG.
#
# Usage: ./Scripts/notarize.sh [dmg-path]
# Defaults to dist/TernisLink-$(cat VERSION).dmg.
#
# Prerequisites (skipped gracefully when absent — this script never fails
# a local build, it just explains what is missing):
#   - A "Developer ID Application" identity in the login keychain
#     (override with env CODESIGN_IDENTITY="Developer ID Application: …").
#   - Notarization credentials in the keychain (xcrun notarytool with either
#     a stored profile: env NOTARY_PROFILE, or APPLE_ID + APP_PASSWORD +
#     TEAM_ID env vars).
#
# Flow: codesign bundle (runtime hardening) → rebuild DMG contents →
# sign DMG → notarytool submit --wait → stapler staple.
set -eu
cd "$(dirname "$0")/.."

VERSION="$(tr -d '[:space:]' < VERSION)"
DMG="${1:-dist/TernisLink-${VERSION}.dmg}"

fail_skip() {
    echo "notarize.sh: SKIP — $1" >&2
    echo "notarize.sh: ship the ad-hoc DMG for local use, or provide credentials and re-run." >&2
    exit 0
}

[ -f "$DMG" ] || fail_skip "no DMG at $DMG (run ./Scripts/dmg.sh first)."
command -v xcrun >/dev/null 2>&1 || fail_skip "Xcode CLT missing (need xcrun notarytool/stapler)."

IDENTITY="${CODESIGN_IDENTITY:-$(security find-identity -v -p codesigning 2>/dev/null | grep 'Developer ID Application' | head -1 | sed 's/.*"\(.*\)".*/\1/')}"
[ -n "$IDENTITY" ] || fail_skip "no Developer ID Application identity (CODESIGN_IDENTITY unset)."

# Harden + sign the staged bundle inside a fresh DMG build.
./Scripts/bundle.sh --release
codesign --force --deep --options runtime --timestamp \
    --entitlements Scripts/TernisLink.entitlements \
    --sign "$IDENTITY" dist/TernisLink.app

STAGING="$(mktemp -d)"
trap 'rm -rf "$STAGING"' EXIT
cp -R dist/TernisLink.app "$STAGING/"
ln -s /Applications "$STAGING/Applications"
rm -f "$DMG" dist/TernisLink-latest.dmg
hdiutil create -volname "ternis.link $VERSION" \
    -srcfolder "$STAGING" -ov -format UDZO \
    "dist/TernisLink-${VERSION}.dmg" >/dev/null
codesign --force --sign "$IDENTITY" "dist/TernisLink-${VERSION}.dmg"

if [ -n "${NOTARY_PROFILE:-}" ]; then
    xcrun notarytool submit "dist/TernisLink-${VERSION}.dmg" \
        --keychain-profile "$NOTARY_PROFILE" --wait
elif [ -n "${APPLE_ID:-}" ] && [ -n "${APP_PASSWORD:-}" ] && [ -n "${TEAM_ID:-}" ]; then
    xcrun notarytool submit "dist/TernisLink-${VERSION}.dmg" \
        --apple-id "$APPLE_ID" --password "$APP_PASSWORD" --team-id "$TEAM_ID" --wait
else
    fail_skip "signed OK, but no notary credentials (NOTARY_PROFILE or APPLE_ID/APP_PASSWORD/TEAM_ID)."
fi

xcrun stapler staple "dist/TernisLink-${VERSION}.dmg"
cp "dist/TernisLink-${VERSION}.dmg" dist/TernisLink-latest.dmg
spctl -a -t open --context context:primary-signature -v "dist/TernisLink-${VERSION}.dmg" || true
echo "Notarized + stapled: dist/TernisLink-${VERSION}.dmg"
