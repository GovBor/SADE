#include <Keypad.h>
#include <SoftwareSerial.h>

// Pin Definitions
#define SOLENOID_PIN 8          // MOSFET gate pin for solenoid control
#define GREEN_LED_PIN 9         // Green LED for success indication
#define RED_LED_PIN 10          // Red LED for failure indication
#define EXIT_BUTTON_PIN 2       // Manual exit button (interrupt pin)
#define REED_SWITCH_PIN 3       // Reed switch for tampering detection (interrupt pin)
#define BUZZER_PIN 11           // Optional buzzer for alerts

// USB Hub Communication (via Serial)
#define USB_HUB_RX_PIN 12       // Connect to USB Hub data line
#define USB_HUB_TX_PIN 13       // Connect to USB Hub data line

// Keypad Configuration
const byte ROWS = 4;
const byte COLS = 4;
char keys[ROWS][COLS] = {
  {'1','2','3','A'},
  {'4','5','6','B'},
  {'7','8','9','C'},
  {'*','0','#','D'}
};
byte rowPins[ROWS] = {4, 5, 6, 7};     // Connect to row pins of keypad
byte colPins[COLS] = {A0, A1, A2, A3}; // Connect to column pins of keypad

Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);
SoftwareSerial usbHubSerial(USB_HUB_RX_PIN, USB_HUB_TX_PIN);

// System Variables
String masterCode = "1234";  // Default technician access code
String inputCode = "";
String lastScannedBarcode = "";
unsigned long doorUnlockTime = 0;
unsigned long keypadLockoutTime = 0;
unsigned long barcodeTimeout = 0;
int failedAttempts = 0;
bool doorLocked = true;
bool keypadLocked = false;
bool tamperingDetected = false;
bool systemOnline = true;
bool barcodeMode = false;

// Timing Constants
const unsigned long DOOR_UNLOCK_DURATION = 5000;    // 5 seconds
const unsigned long KEYPAD_LOCKOUT_DURATION = 300000; // 5 minutes
const unsigned long BARCODE_TIMEOUT = 10000;        // 10 seconds for barcode scan
const unsigned long DEBOUNCE_DELAY = 50;
const int MAX_FAILED_ATTEMPTS = 3;

void setup() {
  Serial.begin(9600);
  usbHubSerial.begin(9600);
  
  // Initialize pins
  pinMode(SOLENOID_PIN, OUTPUT);
  pinMode(GREEN_LED_PIN, OUTPUT);
  pinMode(RED_LED_PIN, OUTPUT);
  pinMode(EXIT_BUTTON_PIN, INPUT_PULLUP);
  pinMode(REED_SWITCH_PIN, INPUT_PULLUP);
  pinMode(BUZZER_PIN, OUTPUT);
  
  // Initialize door to locked state
  lockDoor();
  
  // Setup interrupts
  attachInterrupt(digitalPinToInterrupt(EXIT_BUTTON_PIN), exitButtonPressed, FALLING);
  attachInterrupt(digitalPinToInterrupt(REED_SWITCH_PIN), tamperingDetected_ISR, FALLING);
  
  // Startup indication
  blinkLED(GREEN_LED_PIN, 3);
  
  Serial.println("SADE Door Controller with Barcode Scanner Initialized");
  Serial.println("Access Methods: PIN, Barcode, Facial Recognition, Manual Exit");
  Serial.println("System Ready");
}

void loop() {
  // Check for serial commands from ESP32
  checkSerialCommands();
  
  // Check for barcode scanner input
  checkBarcodeInput();
  
  // Handle keypad input
  handleKeypadInput();
  
  // Check door auto-lock timer
  checkDoorAutoLock();
  
  // Check keypad lockout timer
  checkKeypadLockout();
  
  // Check barcode timeout
  checkBarcodeTimeout();
  
  // Handle tampering alerts
  if (tamperingDetected) {
    handleTampering();
  }
  
  // System status indication
  updateStatusLEDs();
  
  delay(100); // Small delay for system stability
}

void checkSerialCommands() {
  if (Serial.available()) {
    String command = Serial.readStringUntil('\n');
    command.trim();
    
    if (command == "UNLOCK") {
      // Command from ESP32 for authorized access
      unlockDoor();
      Serial.println("DOOR_UNLOCKED");
    }
    else if (command == "LOCK") {
      lockDoor();
      Serial.println("DOOR_LOCKED");
    }
    else if (command == "STATUS") {
      sendSystemStatus();
    }
    else if (command == "BARCODE_MODE") {
      enableBarcodeMode();
    }
    else if (command.startsWith("BARCODE_SCAN:")) {
      // Handle barcode from ESP32 processing
      String barcode = command.substring(13);
      processBarcodeAccess(barcode);
    }
    else if (command.startsWith("SET_CODE:")) {
      // Update master code: SET_CODE:newcode
      String newCode = command.substring(9);
      if (newCode.length() >= 4 && newCode.length() <= 8) {
        masterCode = newCode;
        Serial.println("CODE_UPDATED");
        blinkLED(GREEN_LED_PIN, 2);
      } else {
        Serial.println("INVALID_CODE_LENGTH");
        blinkLED(RED_LED_PIN, 2);
      }
    }
    else if (command == "RESET_ATTEMPTS") {
      failedAttempts = 0;
      keypadLocked = false;
      Serial.println("ATTEMPTS_RESET");
    }
  }
}

void checkBarcodeInput() {
  if (usbHubSerial.available()) {
    String barcodeData = usbHubSerial.readStringUntil('\n');
    barcodeData.trim();
    
    if (barcodeData.length() > 0) {
      Serial.print("BARCODE_SCANNED:");
      Serial.println(barcodeData);
      
      // Process barcode for student access
      processBarcodeAccess(barcodeData);
    }
  }
}

void processBarcodeAccess(String barcode) {
  // Send barcode to ESP32 for database verification
  Serial.print("VERIFY_BARCODE:");
  Serial.println(barcode);
  
  lastScannedBarcode = barcode;
  barcodeTimeout = millis();
  
  // Visual feedback for barcode scan
  blinkLED(GREEN_LED_PIN, 1);
  tone(BUZZER_PIN, 800, 100);
  
  // Note: Actual verification happens via ESP32 communication
  // ESP32 will send back UNLOCK command if barcode is valid
}

void enableBarcodeMode() {
  barcodeMode = true;
  barcodeTimeout = millis();
  
  // Visual indication for barcode mode
  digitalWrite(GREEN_LED_PIN, HIGH);
  tone(BUZZER_PIN, 1200, 200);
  delay(100);
  tone(BUZZER_PIN, 1500, 200);
  
  Serial.println("BARCODE_MODE_ENABLED");
}

void checkBarcodeTimeout() {
  if (barcodeMode && barcodeTimeout > 0) {
    if (millis() - barcodeTimeout >= BARCODE_TIMEOUT) {
      barcodeMode = false;
      barcodeTimeout = 0;
      digitalWrite(GREEN_LED_PIN, LOW);
      Serial.println("BARCODE_MODE_TIMEOUT");
    }
  }
}

void handleKeypadInput() {
  if (keypadLocked) {
    return; // Keypad is locked due to failed attempts
  }
  
  char key = keypad.getKey();
  
  if (key) {
    Serial.print("Key pressed: ");
    Serial.println(key);
    
    if (key == '#') {
      // Submit code
      if (inputCode == masterCode) {
        // Correct code entered
        unlockDoor();
        resetFailedAttempts();
        Serial.println("TECHNICIAN_ACCESS_GRANTED");
        blinkLED(GREEN_LED_PIN, 3);
      } else {
        // Wrong code
        handleFailedAttempt();
        Serial.println("ACCESS_DENIED");
        blinkLED(RED_LED_PIN, 3);
      }
      inputCode = ""; // Clear input
    }
    else if (key == '*') {
      // Clear current input or enable barcode mode
      if (inputCode.length() == 0) {
        enableBarcodeMode();
      } else {
        inputCode = "";
        Serial.println("INPUT_CLEARED");
      }
    }
    else if (key >= '0' && key <= '9') {
      // Add digit to input
      if (inputCode.length() < 8) { // Max code length
        inputCode += key;
        Serial.print("Input: ");
        // Print asterisks for security
        for (int i = 0; i < inputCode.length(); i++) {
          Serial.print("*");
        }
        Serial.println();
      }
    }
  }
}

void unlockDoor() {
  digitalWrite(SOLENOID_PIN, HIGH); // Activate solenoid (unlock)
  digitalWrite(GREEN_LED_PIN, HIGH);
  digitalWrite(RED_LED_PIN, LOW);
  
  doorLocked = false;
  doorUnlockTime = millis();
  barcodeMode = false; // Exit barcode mode on successful unlock
  
  // Success feedback
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 1000, 150);
    delay(200);
  }
  
  Serial.println("Door unlocked");
}

void lockDoor() {
  digitalWrite(SOLENOID_PIN, LOW);  // Deactivate solenoid (lock)
  digitalWrite(GREEN_LED_PIN, LOW);
  
  doorLocked = true;
  doorUnlockTime = 0;
  barcodeMode = false;
  
  Serial.println("Door locked");
}

void checkDoorAutoLock() {
  if (!doorLocked && doorUnlockTime > 0) {
    if (millis() - doorUnlockTime >= DOOR_UNLOCK_DURATION) {
      lockDoor();
      Serial.println("Door auto-locked");
    }
  }
}

void handleFailedAttempt() {
  failedAttempts++;
  
  // Blink red LED to indicate number of failed attempts
  blinkLED(RED_LED_PIN, failedAttempts);
  
  if (failedAttempts >= MAX_FAILED_ATTEMPTS) {
    keypadLocked = true;
    keypadLockoutTime = millis();
    Serial.println("KEYPAD_LOCKED");
    Serial.println("SECURITY_ALERT:MULTIPLE_FAILED_ATTEMPTS");
    
    // Sound alarm
    for (int i = 0; i < 5; i++) {
      tone(BUZZER_PIN, 2000, 200);
      delay(300);
    }
  }
}

void checkKeypadLockout() {
  if (keypadLocked && keypadLockoutTime > 0) {
    if (millis() - keypadLockoutTime >= KEYPAD_LOCKOUT_DURATION) {
      keypadLocked = false;
      failedAttempts = 0;
      keypadLockoutTime = 0;
      Serial.println("KEYPAD_UNLOCKED");
      blinkLED(GREEN_LED_PIN, 1);
    }
  }
}

void resetFailedAttempts() {
  failedAttempts = 0;
  keypadLocked = false;
  keypadLockoutTime = 0;
}

void exitButtonPressed() {
  // Interrupt service routine for exit button
  static unsigned long lastInterruptTime = 0;
  unsigned long interruptTime = millis();
  
  // Debounce
  if (interruptTime - lastInterruptTime > DEBOUNCE_DELAY) {
    unlockDoor();
    Serial.println("MANUAL_EXIT_ACTIVATED");
  }
  lastInterruptTime = interruptTime;
}

void tamperingDetected_ISR() {
  // Interrupt service routine for reed switch
  static unsigned long lastTamperTime = 0;
  unsigned long tamperTime = millis();
  
  // Debounce
  if (tamperTime - lastTamperTime > DEBOUNCE_DELAY) {
    tamperingDetected = true;
  }
  lastTamperTime = tamperTime;
}

void handleTampering() {
  Serial.println("SECURITY_ALERT:TAMPERING_DETECTED");
  
  // Flash red LED rapidly
  for (int i = 0; i < 10; i++) {
    digitalWrite(RED_LED_PIN, HIGH);
    delay(100);
    digitalWrite(RED_LED_PIN, LOW);
    delay(100);
  }
  
  // Sound alarm
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 3000, 500);
    delay(600);
  }
  
  tamperingDetected = false; // Reset flag
}

void updateStatusLEDs() {
  if (barcodeMode) {
    // Slow blink green LED when in barcode mode
    static unsigned long lastBlink = 0;
    static bool ledState = false;
    
    if (millis() - lastBlink >= 500) {
      ledState = !ledState;
      digitalWrite(GREEN_LED_PIN, ledState);
      lastBlink = millis();
    }
  } else if (keypadLocked) {
    // Slow blink red LED when keypad is locked
    static unsigned long lastBlink = 0;
    static bool ledState = false;
    
    if (millis() - lastBlink >= 1000) {
      ledState = !ledState;
      digitalWrite(RED_LED_PIN, ledState);
      lastBlink = millis();
    }
  } else if (doorLocked) {
    // Steady red when door is locked and system ready
    digitalWrite(RED_LED_PIN, HIGH);
    if (!barcodeMode) {
      digitalWrite(GREEN_LED_PIN, LOW);
    }
  }
}

void blinkLED(int pin, int times) {
  for (int i = 0; i < times; i++) {
    digitalWrite(pin, HIGH);
    delay(200);
    digitalWrite(pin, LOW);
    delay(200);
  }
}

void sendSystemStatus() {
  Serial.print("STATUS:");
  Serial.print("DOOR=");
  Serial.print(doorLocked ? "LOCKED" : "UNLOCKED");
  Serial.print(",KEYPAD=");
  Serial.print(keypadLocked ? "LOCKED" : "READY");
  Serial.print(",BARCODE=");
  Serial.print(barcodeMode ? "ACTIVE" : "READY");
  Serial.print(",ATTEMPTS=");
  Serial.print(failedAttempts);
  Serial.print(",UPTIME=");
  Serial.println(millis());
}

// Emergency functions for fail-safe operation
void emergencyUnlock() {
  // This function can be called in case of power failure
  digitalWrite(SOLENOID_PIN, LOW); // Ensure solenoid is off
  Serial.println("EMERGENCY_UNLOCK_ACTIVATED");
}

void systemReset() {
  // Reset all variables to initial state
  failedAttempts = 0;
  keypadLocked = false;
  keypadLockoutTime = 0;
  inputCode = "";
  tamperingDetected = false;
  barcodeMode = false;
  barcodeTimeout = 0;
  lastScannedBarcode = "";
  lockDoor();
  Serial.println("SYSTEM_RESET_COMPLETE");
}
