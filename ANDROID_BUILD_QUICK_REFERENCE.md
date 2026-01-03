# Android Build Quick Reference

Quick checklist and commands for building and releasing your Android app.

## Quick Setup Commands

```bash
# 1. Install Capacitor
cd frontend
npm install @capacitor/core @capacitor/cli @capacitor/app @capacitor/haptics @capacitor/keyboard @capacitor/status-bar

# 2. Initialize Capacitor (if not done)
npx cap init "School Management System" "com.yourschool.sms"

# 3. Add Android platform
npx cap add android

# 4. Build Quasar app
quasar build -m android

# 5. Sync to Android
npx cap sync android

# 6. Open in Android Studio
npx cap open android
```

## Build Commands

```bash
# Build for Android
quasar build -m android

# Sync Capacitor
npx cap sync android

# Build debug APK (command line)
cd src-capacitor/android
./gradlew assembleDebug

# Build release AAB (command line)
cd src-capacitor/android
./gradlew bundleRelease
```

## Version Update Checklist

Before each release:

- [ ] Update `versionCode` in `src-capacitor/android/app/build.gradle`
- [ ] Update `versionName` in `src-capacitor/android/app/build.gradle`
- [ ] Update `version` in `frontend/package.json`
- [ ] Update CHANGELOG.md
- [ ] Test on multiple devices
- [ ] Build release AAB
- [ ] Upload to Play Console

## Pre-Release Checklist

- [ ] App builds without errors
- [ ] All features tested
- [ ] No critical bugs
- [ ] API endpoints are production-ready
- [ ] App icons and splash screens updated
- [ ] Version numbers incremented
- [ ] Release notes prepared
- [ ] AAB file signed correctly
- [ ] Keystore file backed up

## Play Store Upload Checklist

- [ ] Store listing completed
- [ ] Screenshots uploaded (2-8 required)
- [ ] Feature graphic uploaded
- [ ] App icon uploaded (512x512)
- [ ] Privacy policy URL provided
- [ ] Data safety form completed
- [ ] Content rating completed
- [ ] App access configured
- [ ] AAB file uploaded
- [ ] Release notes added

## Common File Locations

```
frontend/
├── src-capacitor/
│   └── android/
│       ├── app/
│       │   ├── build.gradle          # App configuration
│       │   └── src/main/
│       │       ├── AndroidManifest.xml
│       │       ├── res/               # Icons, strings, etc.
│       │       └── assets/
│       └── keystore.properties        # Signing config (gitignored)
├── dist/spa/                          # Built web assets
└── package.json
```

## Version Numbering

**Version Code** (build.gradle):
- Always increment: 1, 2, 3, 4...
- Cannot decrease
- Used by Play Store to determine which version is newer

**Version Name** (build.gradle):
- User-visible: 1.0.0, 1.0.1, 1.1.0
- Follow semantic versioning: MAJOR.MINOR.PATCH

**Example:**
```gradle
versionCode 5
versionName "1.2.1"
```

## Signing Key Commands

```bash
# Generate signing key
keytool -genkey -v -keystore sms-release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias sms-key

# Verify key
keytool -list -v -keystore sms-release-key.jks

# Change password
keytool -storepasswd -keystore sms-release-key.jks
```

## Testing URLs

After uploading to Play Console:

- **Internal Testing**: Share opt-in URL with testers
- **Closed Testing**: Share opt-in URL with testers
- **Open Testing**: Public beta URL on Play Store

## Troubleshooting Quick Fixes

**Build fails:**
```bash
# Clean build
cd src-capacitor/android
./gradlew clean
./gradlew bundleRelease
```

**Sync issues:**
```bash
# Remove and re-add Android
npx cap remove android
npx cap add android
npx cap sync android
```

**Android Studio issues:**
- File → Invalidate Caches → Invalidate and Restart
- Build → Clean Project
- Build → Rebuild Project

## Environment Variables

**Windows:**
```cmd
set ANDROID_HOME=C:\Users\YourUsername\AppData\Local\Android\Sdk
set PATH=%PATH%;%ANDROID_HOME%\platform-tools
```

**macOS/Linux:**
```bash
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH=$PATH:$ANDROID_HOME/platform-tools
```

## Useful Links

- [Play Console](https://play.google.com/console)
- [Quasar Capacitor Docs](https://quasar.dev/quasar-cli/developing-capacitor-apps/introduction)
- [Capacitor Docs](https://capacitorjs.com/docs)
- [Android Developer Guide](https://developer.android.com/guide)

---

For detailed instructions, see `ANDROID_BUILD_AND_PLAYSTORE_SETUP.md`

