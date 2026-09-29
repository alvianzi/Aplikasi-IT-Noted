# IT Noted Android wrapper

This is a small Android WebView app for `https://itnoted.infy.click/`. It keeps login cookies on the device, supports image uploads, and opens external links in the browser.

Build a directly installable debug-signed APK with Android SDK Platform 36, Build Tools 36.0.0, JDK 17, and Gradle 9.5:

```powershell
gradle assembleDebug
```

The APK is written to `app/build/outputs/apk/debug/app-debug.apk`. The debug signature is suitable for private sideloading and testing, not publishing as a Play Store release. Keep the same signing key to provide seamless app updates.
