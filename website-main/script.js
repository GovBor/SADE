document.addEventListener("DOMContentLoaded", () => {
  const toggleButtons = document.querySelectorAll(".toggle-btn")
  const userTypeInput = document.getElementById("userType")
  const loginForm = document.getElementById("loginForm")
  const errorMessage = document.getElementById("errorMessage")
  const pinInput = document.getElementById("pin")

  // Handle user type toggle
  toggleButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Remove active class from all buttons
      toggleButtons.forEach((btn) => btn.classList.remove("active"))

      // Add active class to clicked button
      this.classList.add("active")

      // Update hidden input value
      userTypeInput.value = this.dataset.type

      // Clear any error messages
      hideError()
    })
  })

  // Handle form submission
  loginForm.addEventListener("submit", (e) => {
    e.preventDefault()

    const pin = pinInput.value.trim()
    const userType = userTypeInput.value

    // Basic validation
    if (!pin) {
      showError("Please enter your PIN")
      return
    }

    if (pin.length < 4) {
      showError("PIN must be at least 4 digits")
      return
    }

    // Show loading state
    const submitBtn = document.querySelector(".signin-btn")
    const originalText = submitBtn.textContent
    submitBtn.textContent = "Signing In..."
    submitBtn.disabled = true

    // Submit form data
    const formData = new FormData()
    formData.append("pin", pin)
    formData.append("user_type", userType)

    fetch("login.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Redirect to dashboard
          window.location.href = data.redirect
        } else {
          showError(data.message)
        }
      })
      .catch((error) => {
        showError("Connection error. Please try again.")
        console.error("Error:", error)
      })
      .finally(() => {
        // Reset button state
        submitBtn.textContent = originalText
        submitBtn.disabled = false
      })
  })

  // PIN input formatting (numbers only)
  pinInput.addEventListener("input", function (e) {
    // Allow only numbers
    this.value = this.value.replace(/[^0-9]/g, "")
  })

  // Clear error on input
  pinInput.addEventListener("input", () => {
    hideError()
  })

  function showError(message) {
    errorMessage.textContent = message
    errorMessage.style.display = "block"
  }

  function hideError() {
    errorMessage.style.display = "none"
  }
})
