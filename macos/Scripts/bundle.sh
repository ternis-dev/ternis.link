#!/bin/sh
# Dev bundle: assembles dist/TernisLink.app so the app launches as a REAL
# macOS application (activation + key events, Edit menu incl. paste, dock
# icon, ternislink:// URL scheme for SSO).
#
# Why not `swift run`: that executes a raw binary with no bundle and no
# Info.plist — macOS never properly activates it, so typing/paste break
# and custom URL schemes are unregistered.
#
# Usage: ./Scripts/bundle.sh [--release]
# Then:  open dist/TernisLink.app
set -eu
cd "$(dirname "$0")/.."

if [ "${1:-}" = "--release" ]; then
    BUILD_ARGS="--configuration release"
    CONFIG=release
else
    BUILD_ARGS=""
    CONFIG=debug
fi

# shellcheck disable=SC2086
swift build $BUILD_ARGS

APP=dist/TernisLink.app
rm -rf "$APP"
mkdir -p "$APP/Contents/MacOS" "$APP/Contents/Resources"
cp ".build/$CONFIG/TernisLink" "$APP/Contents/MacOS/"

cat > "$APP/Contents/Info.plist" <<'PLIST'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>CFBundleName</key><string>ternis.link</string>
    <key>CFBundleDisplayName</key><string>ternis.link</string>
    <key>CFBundleIdentifier</key><string>link.ternis.desktop</string>
    <key>CFBundleVersion</key><string>1</string>
    <key>CFBundleShortVersionString</key><string>0.1.0</string>
    <key>CFBundlePackageType</key><string>APPL</string>
    <key>CFBundleSignature</key><string>????</string>
    <key>CFBundleExecutable</key><string>TernisLink</string>
    <key>LSMinimumSystemVersion</key><string>14.0</string>
    <key>CFBundleURLTypes</key>
    <array><dict>
        <key>CFBundleURLName</key><string>OAuth callback</string>
        <key>CFBundleURLSchemes</key><array><string>ternislink</string></array>
    </dict></array>
</dict>
</plist>
PLIST

codesign --force --deep --sign - "$APP"
echo "Built $APP — launch with: open $APP"
