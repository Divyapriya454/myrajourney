# MyRA Journey - Android App Source

## Requirements
- Android Studio Hedgehog or newer
- JDK 17
- Android SDK 34

## Setup
1. Open this folder in Android Studio
2. Update `app/local.properties` with your SDK path:
   ```
   sdk.dir=C\:\\Users\\YourName\\AppData\\Local\\Android\\Sdk
   ```
3. The backend URL is already set to the college server in:
   `app/src/main/res/values/network_config.xml`

## Build for Play Store
1. Build → Generate Signed Bundle/APK
2. Choose **Android App Bundle (.aab)** for Play Store
3. Create or use existing keystore
4. Select **release** build variant
5. Upload the generated `.aab` file to Google Play Console

## Package Name
`com.simats.myrajourney`

## Min SDK: 24 (Android 7.0)
## Target SDK: 34 (Android 14)
