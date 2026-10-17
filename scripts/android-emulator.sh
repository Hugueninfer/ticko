#!/bin/sh
# Android emulator for the customer app, without Android Studio and without sudo.
#
#   scripts/android-emulator.sh setup   install the SDK packages and create the "ticko" AVD
#                                       (asks you to accept Google's Android SDK licenses)
#   scripts/android-emulator.sh start   boot the emulator (window) and wait until it is ready
#
# Needs Java 17+ (JAVA_HOME or `java` on PATH), KVM access (/dev/kvm) and the Android
# command-line tools in $ANDROID_HOME/cmdline-tools/latest (default ~/Android/Sdk):
# https://developer.android.com/studio#command-line-tools-only
set -eu

ANDROID_HOME=${ANDROID_HOME:-$HOME/Android/Sdk}
IMAGE=${ANDROID_IMAGE:-"system-images;android-35;default;x86_64"}
AVD=${ANDROID_AVD:-ticko}
export ANDROID_HOME ANDROID_SDK_ROOT=$ANDROID_HOME
PATH=$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools:$ANDROID_HOME/emulator:$PATH

[ -x "$ANDROID_HOME/cmdline-tools/latest/bin/sdkmanager" ] || {
  echo "Android command-line tools not found in $ANDROID_HOME/cmdline-tools/latest." >&2; exit 1; }
[ -r /dev/kvm ] && [ -w /dev/kvm ] || echo "Warning: no access to /dev/kvm; the emulator will be very slow." >&2

case "${1:-start}" in
  setup)
    sdkmanager --licenses
    sdkmanager --install "platform-tools" "emulator" "$IMAGE"
    if ! avdmanager list avd -c | grep -qx "$AVD"; then
      echo no | avdmanager create avd --name "$AVD" --package "$IMAGE" --device "pixel_7"
    fi
    # A 3 GB data partition is plenty for Expo Go (the default needs 12 GB free).
    config="$HOME/.android/avd/$AVD.avd/config.ini"
    sed -i '/^disk.dataPartition.size=/d' "$config" && echo "disk.dataPartition.size=3G" >> "$config"
    echo "AVD \"$AVD\" ready. Next: make mobile-emulator"
    ;;
  start)
    if adb devices | grep -q "^emulator-"; then
      echo "An emulator is already running."
    else
      # 2 GB of RAM, no boot snapshot (smaller on disk), GPU through the host.
      nohup emulator -avd "$AVD" -memory 2048 -partition-size 3072 -no-snapshot -gpu auto -no-audio >"${TMPDIR:-/tmp}/ticko-emulator.log" 2>&1 &
    fi
    adb wait-for-device
    until [ "$(adb shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" = 1 ]; do sleep 2; done
    echo "Emulator ready."
    ;;
  *) echo "usage: $0 setup|start" >&2; exit 2 ;;
esac
