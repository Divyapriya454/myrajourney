plugins {
    alias(libs.plugins.android.application)
}

android {
    // ✅ FIXED TYPO: myrajouney -> myrajourney
    namespace = "com.example.myrajourney"
    compileSdk = 35

    defaultConfig {
        // ✅ FIXED TYPO: myrajouney -> myrajourney
        applicationId = "com.example.myrajourney"
        minSdk = 24
        targetSdk = 35
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"

        // Configurable API base URL
        val apiBaseUrl: String = (project.findProperty("API_BASE_URL") as String?)
            ?: System.getenv("API_BASE_URL")
            ?: ""
        buildConfigField("String", "API_BASE_URL", "\"$apiBaseUrl\"")
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    buildFeatures {
        buildConfig = true
    }
    
    lint {
        abortOnError = false
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    packaging {
        jniLibs {
            useLegacyPackaging = false
        }
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
}

dependencies {
    implementation(libs.appcompat)
    implementation(libs.material)

    // ✅ 1. Security Crypto (Required for TokenManager)
    implementation("androidx.security:security-crypto:1.0.0")

    // ✅ 2. Glide for image loading
    implementation("com.github.bumptech.glide:glide:4.16.0")
    annotationProcessor("com.github.bumptech.glide:compiler:4.16.0")

    // ✅ 3. ViewModel and LiveData
    implementation("androidx.lifecycle:lifecycle-viewmodel:2.7.0")
    implementation("androidx.lifecycle:lifecycle-livedata:2.7.0")

    // ✅ 4. UI Components
    implementation("androidx.recyclerview:recyclerview:1.3.2")
    implementation("androidx.cardview:cardview:1.0.0")
    implementation("androidx.constraintlayout:constraintlayout:2.1.4")
    implementation("androidx.gridlayout:gridlayout:1.0.0")
    // ✅ 5. Networking - Retrofit & OkHttp
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    // ✅ 6. JSON parsing
    implementation("com.google.code.gson:gson:2.10.1")

    // ✅ 7. MPAndroidChart for CRP progress graph
    implementation("com.github.PhilJay:MPAndroidChart:v3.1.0")

    // ✅ 8. Room Database
    implementation("androidx.room:room-runtime:2.6.1")
    annotationProcessor("androidx.room:room-compiler:2.6.1")

    // ✅ 9. CameraX for exercise tracking
    implementation("androidx.camera:camera-core:1.4.1")
    implementation("androidx.camera:camera-camera2:1.4.1")
    implementation("androidx.camera:camera-lifecycle:1.4.1")
    implementation("androidx.camera:camera-view:1.4.1")
    implementation("androidx.camera:camera-extensions:1.4.1")

    // ✅ 10. Guava for ListenableFuture
    implementation("com.google.guava:guava:32.1.3-android")

    // ✅ 11. Fragment and Activity
    implementation("androidx.fragment:fragment:1.6.2")
    implementation("androidx.activity:activity:1.8.2")

    // ✅ 12. SwipeRefreshLayout
    implementation("androidx.swiperefreshlayout:swiperefreshlayout:1.1.0")

    // ✅ 13. Work Manager for background tasks
    implementation("androidx.work:work-runtime:2.9.0")

    // ✅ 14. Navigation Components
    implementation("androidx.navigation:navigation-fragment:2.7.6")
    implementation("androidx.navigation:navigation-ui:2.7.6")

    // ✅ 15. ML Kit Pose Detection for exercise tracking
    implementation("com.google.mlkit:pose-detection:18.0.0-beta5")
    implementation("com.google.mlkit:pose-detection-accurate:18.0.0-beta5")
    
    // ✅ 16. TensorFlow Lite for ML models
    // NOTE: Original dependencies (v2.16.1) cause 16KB alignment warnings on Android 15+.
    // Since no direct usage was found in code, these are commented out to fix the warning.
    // If TFLite is needed, migrate to LiteRT: implementation("com.google.ai.edge.litert:litert:1.4.1")
    // implementation("org.tensorflow:tensorflow-lite:2.16.1")
    // implementation("org.tensorflow:tensorflow-lite-support:0.4.4")
    
    // ✅ 17. ExoPlayer for video playback (reference videos)
    implementation("androidx.media3:media3-exoplayer:1.2.1")
    implementation("androidx.media3:media3-ui:1.2.1")
    
    // ✅ 18. Image analysis for CameraX
    implementation("androidx.camera:camera-mlkit-vision:1.4.1")
    
    // ✅ 19. Math and statistics libraries
    implementation("org.apache.commons:commons-math3:3.6.1")

    // Testing
    testImplementation(libs.junit)
    androidTestImplementation(libs.ext.junit)
    androidTestImplementation(libs.espresso.core)
    // ✅ 20. MediaPipe Hands for accurate wrist/finger tracking
    implementation("com.google.mediapipe:tasks-vision:0.10.29")
}