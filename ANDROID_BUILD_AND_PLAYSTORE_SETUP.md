# Android Build & Google Play Store Setup Guide

Complete guide for building your School Management System Android app and publishing it to the Google Play Store.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [Android App Configuration](#android-app-configuration)
4. [Building the Android App](#building-the-android-app)
5. [App Signing](#app-signing)
6. [Google Play Console Setup](#google-play-console-setup)
7. [App Listing Requirements](#app-listing-requirements)
8. [Testing Tracks](#testing-tracks)
9. [Release Process](#release-process)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### 1. Required Software

Install the following on your development machine:

#### **Java Development Kit (JDK)**
- **Version**: JDK 17 or higher (required for Android builds)
- **Download**: [Oracle JDK](https://www.oracle.com/java/technologies/downloads/) or [OpenJDK](https://adoptium.net/)
- **Verify Installation**:
  ```bash
  java -version
  javac -version
  ```

#### **Android Studio**
- **Download**: [Android Studio](https://developer.android.com/studio)
- **Install**: Follow the installation wizard
- **SDK Components**: Install Android SDK, Android SDK Platform-Tools, and Android SDK Build-Tools
- **Verify Installation**:
  ```bash
  adb version
  ```

#### **Node.js & npm**
- **Version**: Node.js 18+ and npm 9+
- **Verify Installation**:
  ```bash
  node -v
  npm -v
  ```

#### **Quasar CLI** (if not already installed)
```bash
npm install -g @quasar/cli
```

### 2. Environment Variables

Add Android SDK paths to your environment:

**Windows:**
```bash
# Add to System Environment Variables
ANDROID_HOME=C:\Users\YourUsername\AppData\Local\Android\Sdk
PATH=%PATH%;%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools
```

**macOS/Linux:**
```bash
# Add to ~/.bashrc or ~/.zshrc
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH=$PATH:$ANDROID_HOME/platform-tools:$ANDROID_HOME/tools
```

**Verify:**
```bash
echo $ANDROID_HOME  # macOS/Linux
echo %ANDROID_HOME% # Windows
```

### 3. Google Play Developer Account

- **Cost**: One-time $25 registration fee
- **Sign up**: [Google Play Console](https://play.google.com/console)
- **Required**: Google account, payment method, developer information

---

## Initial Setup

### 1. Add Capacitor to Your Project

If Capacitor is not already added:

```bash
cd frontend
npm install @capacitor/core @capacitor/cli
npx cap init
```

When prompted:
- **App name**: School Management System (or your preferred name)
- **App ID**: `com.yourschool.sms` (use reverse domain notation)
- **Web dir**: `dist/spa`

### 2. Add Android Platform

```bash
npx cap add android
```

This creates the `src-capacitor/android` directory.

### 3. Install Capacitor Plugins (if needed)

Common plugins you might need:

```bash
npm install @capacitor/app
npm install @capacitor/haptics
npm install @capacitor/keyboard
npm install @capacitor/status-bar
npm install @capacitor/network
npm install @capacitor/storage
```

---

## Android App Configuration

### 1. Configure App ID and Version

Edit `src-capacitor/android/app/build.gradle`:

```gradle
android {
    namespace "com.yourschool.sms"
    compileSdkVersion 34
    
    defaultConfig {
        applicationId "com.yourschool.sms"
        minSdkVersion 22
        targetSdkVersion 34
        versionCode 1
        versionName "1.0.0"
    }
}
```

**Important Notes:**
- `applicationId`: Must match your Play Store package name (cannot be changed later)
- `versionCode`: Increment for each release (1, 2, 3, ...)
- `versionName`: User-visible version (1.0.0, 1.0.1, etc.)
- `minSdkVersion`: Minimum Android version (22 = Android 5.1)

### 2. Configure App Name and Icons

#### **App Name**

Edit `src-capacitor/android/app/src/main/res/values/strings.xml`:

```xml
<?xml version='1.0' encoding='utf-8'?>
<resources>
    <string name="app_name">School Management System</string>
    <string name="package_name">com.yourschool.sms</string>
    <string name="custom_url_scheme">sms</string>
</resources>
```

#### **App Icons**

Generate icons using [App Icon Generator](https://www.appicon.co/) or [IconKitchen](https://icon.kitchen/):

**Required Sizes:**
- **Adaptive Icon**: 1024x1024px (foreground + background)
- **Legacy Icon**: 512x512px

**Place Icons:**
1. Copy adaptive icons to:
   - `src-capacitor/android/app/src/main/res/mipmap-anydpi-v26/ic_launcher.xml`
   - `src-capacitor/android/app/src/main/res/mipmap-mdpi/ic_launcher.png` (48x48)
   - `src-capacitor/android/app/src/main/res/mipmap-hdpi/ic_launcher.png` (72x72)
   - `src-capacitor/android/app/src/main/res/mipmap-xhdpi/ic_launcher.png` (96x96)
   - `src-capacitor/android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png` (144x144)
   - `src-capacitor/android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png` (192x192)

2. Or use Quasar's icon generation:
   ```bash
   quasar build -m android
   # Then manually replace icons in the generated folders
   ```

#### **Splash Screen**

Edit `src-capacitor/android/app/src/main/res/values/styles.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <style name="AppSplashScreen" parent="Theme.SplashScreen">
        <item name="windowSplashScreenBackground">@color/splash_background</item>
        <item name="windowSplashScreenAnimatedIcon">@mipmap/ic_launcher</item>
        <item name="postSplashScreenTheme">@style/AppTheme</item>
    </style>
</resources>
```

### 3. Configure Permissions

Edit `src-capacitor/android/app/src/main/AndroidManifest.xml`:

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <!-- Add other permissions as needed -->
    
    <application
        android:label="@string/app_name"
        android:icon="@mipmap/ic_launcher"
        android:allowBackup="true"
        android:usesCleartextTraffic="true">
        <!-- Your activity configurations -->
    </application>
</manifest>
```

### 4. Configure API Endpoints

Create `src-capacitor/android/app/src/main/assets/config.json`:

```json
{
  "apiUrl": "https://api.yourschool.com",
  "environment": "production"
}
```

Update your API service to read from this config in production.

---

## Building the Android App

### 1. Build Quasar App

```bash
cd frontend
quasar build -m android
```

This creates the optimized web assets in `dist/spa/`.

### 2. Sync Capacitor

```bash
npx cap sync android
```

This copies the web assets to the Android project.

### 3. Open in Android Studio

```bash
npx cap open android
```

Or manually open:
```
src-capacitor/android/
```

### 4. Build APK (for testing)

In Android Studio:
1. **Build** → **Build Bundle(s) / APK(s)** → **Build APK(s)**
2. Wait for build to complete
3. APK location: `src-capacitor/android/app/build/outputs/apk/debug/app-debug.apk`

**Or via command line:**
```bash
cd src-capacitor/android
./gradlew assembleDebug
```

### 5. Build AAB (for Play Store)

**In Android Studio:**
1. **Build** → **Generate Signed Bundle / APK**
2. Select **Android App Bundle**
3. Follow signing wizard (see [App Signing](#app-signing) section)

**Or via command line:**
```bash
cd src-capacitor/android
./gradlew bundleRelease
```

---

## App Signing

### 1. Generate Signing Key

**Important**: Keep this key secure! You'll need it for all future updates.

```bash
keytool -genkey -v -keystore sms-release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias sms-key
```

**Information to provide:**
- **Keystore password**: (create a strong password)
- **Key password**: (can be same as keystore password)
- **Name**: Your name or organization
- **Organizational Unit**: Department (optional)
- **Organization**: Your school/organization name
- **City**: Your city
- **State**: Your state/province
- **Country**: Two-letter code (e.g., GH for Ghana)

**Store the key securely:**
- Save `sms-release-key.jks` in a secure location
- **Backup**: Create multiple backups in different secure locations
- **Document**: Save the passwords in a secure password manager

### 2. Configure Signing in Android Studio

1. **File** → **Project Structure** → **Modules** → **app**
2. **Signing Configs** tab:
   - Click **+** to add new config
   - **Name**: `release`
   - **Store File**: Path to your `sms-release-key.jks`
   - **Store Password**: Your keystore password
   - **Key Alias**: `sms-key`
   - **Key Password**: Your key password

3. **Build Types** tab:
   - Select **release**
   - **Signing Config**: Select `release`

### 3. Configure Signing in build.gradle

Edit `src-capacitor/android/app/build.gradle`:

```gradle
android {
    signingConfigs {
        release {
            storeFile file('path/to/sms-release-key.jks')
            storePassword 'your-keystore-password'
            keyAlias 'sms-key'
            keyPassword 'your-key-password'
        }
    }
    
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            shrinkResources true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
}
```

**Security Note**: Never commit the keystore file or passwords to Git! Use environment variables or a secure config file.

### 4. Use Environment Variables (Recommended)

Create `src-capacitor/android/keystore.properties` (add to `.gitignore`):

```properties
storePassword=your-keystore-password
keyPassword=your-key-password
keyAlias=sms-key
storeFile=../path/to/sms-release-key.jks
```

Update `build.gradle`:

```gradle
def keystorePropertiesFile = rootProject.file("keystore.properties")
def keystoreProperties = new Properties()
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

android {
    signingConfigs {
        release {
            storeFile file(keystoreProperties['storeFile'])
            storePassword keystoreProperties['storePassword']
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
        }
    }
}
```

---

## Google Play Console Setup

### 1. Create New App

1. Go to [Google Play Console](https://play.google.com/console)
2. Click **Create app**
3. Fill in:
   - **App name**: School Management System
   - **Default language**: English (or your primary language)
   - **App or game**: App
   - **Free or paid**: Free (or Paid if you charge)
   - **Declarations**: Check all applicable boxes
4. Click **Create app**

### 2. Complete App Access

1. **App access**: Choose who can download
   - **Everyone**: Public app
   - **Some countries/regions**: Restricted access
2. **Content rating**: Complete questionnaire
3. **Target audience**: Select age groups
4. **Data safety**: Declare data collection practices

### 3. Set Up App Content

Navigate to **Policy** → **App content** and complete:

- **Privacy Policy**: Required URL
- **Data safety**: Declare data collection
- **Target audience**: Age restrictions
- **Content ratings**: Complete questionnaire

---

## App Listing Requirements

### 1. Store Listing

Navigate to **Store presence** → **Main store listing**:

#### **Required Information:**

- **App name** (50 characters max): School Management System
- **Short description** (80 characters max): 
  ```
  Complete school management solution for parents, teachers, and administrators.
  ```
- **Full description** (4000 characters max):
  ```
  School Management System is a comprehensive mobile application designed for 
  Ghanaian schools to streamline academic operations and enhance parent-school 
  communication.
  
  Features:
  • Student Management: View student profiles, academic records, and attendance
  • Results & Report Cards: Access exam results and term report cards
  • Fee Payments: Pay school fees securely via mobile money
  • Notifications: Receive important announcements and updates
  • Attendance Tracking: Monitor student attendance in real-time
  • Multi-school Support: Each school operates on its own domain
  
  For Parents:
  - Link and manage your children's accounts
  - View academic progress and results
  - Make fee payments securely
  - Receive real-time notifications
  - Access report cards and attendance records
  
  For Teachers:
  - Enter and manage student results
  - Mark attendance
  - Send notifications to parents
  - View class and student information
  
  For Administrators:
  - Complete school management dashboard
  - Manage students, teachers, and classes
  - Generate reports and analytics
  - Configure academic terms and fees
  - Send announcements and notifications
  
  Secure, reliable, and designed specifically for Ghanaian schools.
  ```

#### **Graphics Required:**

1. **App icon** (512x512px PNG, no transparency)
2. **Feature graphic** (1024x500px PNG)
3. **Phone screenshots** (2-8 required):
   - Minimum: 320px to 3840px width
   - Aspect ratio: 16:9 or 9:16
   - Format: PNG or JPG (24-bit)
   - **Recommended sizes**: 1080x1920px (portrait) or 1920x1080px (landscape)
4. **Tablet screenshots** (optional but recommended)
5. **TV screenshots** (if applicable)

#### **Screenshot Guidelines:**

- Show actual app functionality
- Include text overlays explaining features
- Use consistent design language
- Highlight key features:
  - Dashboard
  - Student profiles
  - Results viewing
  - Payment interface
  - Notifications

**Tools for creating screenshots:**
- [Screenshot Framer](https://screenshot.rocks/)
- [App Mockup](https://app-mockup.com/)
- [Mockuphone](https://mockuphone.com/)

### 2. Categorization

- **App category**: Education
- **Tags**: Education, School, Management, Student, Parent
- **Content rating**: Complete questionnaire

### 3. Pricing & Distribution

- **Price**: Free or set price
- **Countries**: Select distribution countries
- **Device compatibility**: Select supported devices

---

## Testing Tracks

### 1. Internal Testing

**Purpose**: Quick testing with up to 100 testers

1. **Release** → **Testing** → **Internal testing**
2. Click **Create new release**
3. Upload AAB file
4. **Release name**: `1.0.0 (1)` (version name and code)
5. **Release notes**: 
   ```
   Initial release
   - Student management
   - Results viewing
   - Fee payments
   - Notifications
   ```
6. Click **Save**
7. **Testers**: Add email addresses (up to 100)

**Testing link**: Share the opt-in URL with testers

### 2. Closed Testing

**Purpose**: Testing with specific groups (up to 20,000 testers)

1. **Release** → **Testing** → **Closed testing**
2. Create test track (Alpha, Beta, etc.)
3. Upload AAB file
4. Add testers via email or Google Groups
5. Testers join via opt-in URL

### 3. Open Testing (Beta)

**Purpose**: Public beta testing

1. **Release** → **Testing** → **Open testing**
2. Upload AAB file
3. Anyone can join via Play Store listing
4. Good for gathering feedback before production

---

## Release Process

### 1. Production Release

1. **Release** → **Production**
2. Click **Create new release**
3. Upload AAB file
4. **Release name**: `1.0.0 (1)`
5. **Release notes**:
   ```
   What's new in this version:
   - Initial release of School Management System
   - Student and parent management
   - Results and report cards
   - Secure fee payments
   - Real-time notifications
   ```
6. Click **Review release**
7. Review all sections:
   - Store listing
   - Content rating
   - Data safety
   - Target audience
   - App access
8. Click **Start rollout to Production**

### 2. Staged Rollout (Recommended)

**Gradual release to minimize risk:**

1. After creating production release, select **Staged rollout**
2. Start with **20%** of users
3. Monitor crash reports and reviews
4. Gradually increase to 50%, 100%

### 3. Update Process

For future updates:

1. **Increment version numbers:**
   - `versionCode`: 2, 3, 4, ...
   - `versionName`: 1.0.1, 1.0.2, 1.1.0, ...

2. **Build new AAB:**
   ```bash
   quasar build -m android
   npx cap sync android
   cd src-capacitor/android
   ./gradlew bundleRelease
   ```

3. **Upload to Play Console:**
   - Same process as initial release
   - Add release notes describing changes

---

## Troubleshooting

### Common Issues

#### **1. Build Errors**

**Error**: `SDK location not found`
**Solution**: Set `ANDROID_HOME` environment variable

**Error**: `Gradle sync failed`
**Solution**: 
- Check internet connection
- Update Gradle: `./gradlew wrapper --gradle-version=8.0`
- Invalidate caches in Android Studio

#### **2. App Crashes on Launch**

**Check:**
- API endpoints are accessible
- Permissions are correctly configured
- Network security config allows HTTP (if using non-HTTPS)

**Debug:**
```bash
adb logcat | grep -i error
```

#### **3. White Screen**

**Causes:**
- JavaScript errors
- API connection issues
- Missing assets

**Debug:**
- Enable remote debugging in Chrome: `chrome://inspect`
- Check browser console for errors

#### **4. Signing Issues**

**Error**: `Keystore file not found`
**Solution**: Verify path in `build.gradle` or `keystore.properties`

**Error**: `Wrong password`
**Solution**: Double-check keystore and key passwords

#### **5. Play Store Rejection**

**Common reasons:**
- Missing privacy policy
- Incomplete data safety form
- Content rating issues
- Missing required graphics
- App crashes during review

**Solution**: Address all issues mentioned in rejection email

---

## Best Practices

### 1. Version Management

- **Version Code**: Always increment (1, 2, 3, ...)
- **Version Name**: Follow semantic versioning (1.0.0, 1.0.1, 1.1.0)
- **Document**: Keep CHANGELOG.md updated

### 2. Testing

- Test on multiple devices and Android versions
- Test with different screen sizes
- Test offline functionality
- Test payment flows thoroughly

### 3. Security

- Use HTTPS for all API calls
- Never commit keystore files
- Use ProGuard/R8 for code obfuscation
- Implement certificate pinning (advanced)

### 4. Performance

- Optimize images and assets
- Use lazy loading
- Minimize bundle size
- Test on low-end devices

### 5. Monitoring

- Set up Firebase Crashlytics
- Monitor Play Console metrics
- Track user reviews and ratings
- Monitor API performance

---

## Checklist Before Publishing

- [ ] App builds successfully
- [ ] AAB file generated and signed
- [ ] App tested on multiple devices
- [ ] All required graphics created (icon, screenshots, feature graphic)
- [ ] Store listing completed
- [ ] Privacy policy URL provided
- [ ] Data safety form completed
- [ ] Content rating questionnaire completed
- [ ] App access configured
- [ ] Internal testing completed
- [ ] No critical bugs or crashes
- [ ] API endpoints are production-ready
- [ ] Keystore file backed up securely
- [ ] Version numbers set correctly
- [ ] Release notes prepared

---

## Additional Resources

- [Quasar Capacitor Documentation](https://quasar.dev/quasar-cli/developing-capacitor-apps/introduction)
- [Capacitor Documentation](https://capacitorjs.com/docs)
- [Google Play Console Help](https://support.google.com/googleplay/android-developer)
- [Android Developer Guide](https://developer.android.com/guide)
- [App Bundle Format](https://developer.android.com/guide/app-bundle)

---

## Support

If you encounter issues:

1. Check Quasar/Capacitor documentation
2. Search Stack Overflow
3. Check GitHub issues for Quasar/Capacitor
4. Review Android Studio logs
5. Check Play Console for specific errors

---

**Last Updated**: 2025-01-20
**Version**: 1.0.0

