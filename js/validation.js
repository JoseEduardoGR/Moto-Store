// Validaciones del lado del cliente
document.addEventListener("DOMContentLoaded", () => {
  // Validación del formulario de login
  const loginForm = document.getElementById("loginForm")
  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      if (!validarLogin()) {
        e.preventDefault()
      }
    })
  }

  // Validación del formulario de registro
  const registerForm = document.getElementById("registerForm")
  if (registerForm) {
    registerForm.addEventListener("submit", (e) => {
      if (!validarRegistro()) {
        e.preventDefault()
      }
    })
  }

  // Validación del formulario de perfil
  const perfilForm = document.getElementById("perfilForm")
  if (perfilForm) {
    perfilForm.addEventListener("submit", (e) => {
      if (!validarPerfil()) {
        e.preventDefault()
      }
    })
  }

  // Validación en tiempo real
  agregarValidacionTiempoReal()
})

function validarLogin() {
  let valido = true

  const email = document.getElementById("email")
  const password = document.getElementById("password")

  // Limpiar errores previos
  limpiarErrores()

  // Validar email
  if (!email.value.trim()) {
    mostrarError("emailError", "El email es obligatorio")
    valido = false
  } else if (!validarFormatoEmail(email.value)) {
    mostrarError("emailError", "Formato de email inválido")
    valido = false
  }

  // Validar contraseña
  if (!password.value.trim()) {
    mostrarError("passwordError", "La contraseña es obligatoria")
    valido = false
  }

  return valido
}

function validarRegistro() {
  let valido = true

  const nombre = document.getElementById("nombre")
  const email = document.getElementById("email")
  const password = document.getElementById("password")
  const confirmPassword = document.getElementById("confirm_password")
  const telefono = document.getElementById("telefono")

  // Limpiar errores previos
  limpiarErrores()

  // Validar nombre
  if (!nombre.value.trim()) {
    mostrarError("nombreError", "El nombre es obligatorio")
    valido = false
  } else if (nombre.value.trim().length < 2) {
    mostrarError("nombreError", "El nombre debe tener al menos 2 caracteres")
    valido = false
  }

  // Validar email
  if (!email.value.trim()) {
    mostrarError("emailError", "El email es obligatorio")
    valido = false
  } else if (!validarFormatoEmail(email.value)) {
    mostrarError("emailError", "Formato de email inválido")
    valido = false
  }

  // Validar contraseña
  if (!password.value) {
    mostrarError("passwordError", "La contraseña es obligatoria")
    valido = false
  } else if (password.value.length < 6) {
    mostrarError("passwordError", "La contraseña debe tener al menos 6 caracteres")
    valido = false
  }

  // Validar confirmación de contraseña
  if (!confirmPassword.value) {
    mostrarError("confirmPasswordError", "Confirma tu contraseña")
    valido = false
  } else if (password.value !== confirmPassword.value) {
    mostrarError("confirmPasswordError", "Las contraseñas no coinciden")
    valido = false
  }

  // Validar teléfono (opcional pero si se ingresa debe ser válido)
  if (telefono.value.trim() && !validarTelefono(telefono.value)) {
    mostrarError("telefonoError", "Formato de teléfono inválido")
    valido = false
  }

  return valido
}

function validarPerfil() {
  let valido = true

  const nombre = document.getElementById("nombre")
  const email = document.getElementById("email")
  const telefono = document.getElementById("telefono")
  const passwordActual = document.getElementById("password_actual")
  const nuevaPassword = document.getElementById("nueva_password")
  const confirmarPassword = document.getElementById("confirmar_password")

  // Limpiar errores previos
  limpiarErrores()

  // Validar nombre
  if (!nombre.value.trim()) {
    mostrarError("nombreError", "El nombre es obligatorio")
    valido = false
  }

  // Validar email
  if (!email.value.trim()) {
    mostrarError("emailError", "El email es obligatorio")
    valido = false
  } else if (!validarFormatoEmail(email.value)) {
    mostrarError("emailError", "Formato de email inválido")
    valido = false
  }

  // Validar teléfono
  if (telefono.value.trim() && !validarTelefono(telefono.value)) {
    mostrarError("telefonoError", "Formato de teléfono inválido")
    valido = false
  }

  // Validar cambio de contraseña
  if (nuevaPassword.value) {
    if (!passwordActual.value) {
      mostrarError("passwordActualError", "Ingresa tu contraseña actual")
      valido = false
    }

    if (nuevaPassword.value.length < 6) {
      mostrarError("nuevaPasswordError", "La nueva contraseña debe tener al menos 6 caracteres")
      valido = false
    }

    if (nuevaPassword.value !== confirmarPassword.value) {
      mostrarError("confirmarPasswordError", "Las contraseñas no coinciden")
      valido = false
    }
  }

  return valido
}

function validarFormatoEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return regex.test(email)
}

function validarTelefono(telefono) {
  const regex = /^[\d\s\-+$$$$]{8,15}$/
  return regex.test(telefono)
}

function mostrarError(elementId, mensaje) {
  const errorElement = document.getElementById(elementId)
  if (errorElement) {
    errorElement.textContent = mensaje
    errorElement.style.display = "block"
  }
}

function limpiarErrores() {
  const errores = document.querySelectorAll(".error-message")
  errores.forEach((error) => {
    error.textContent = ""
    error.style.display = "none"
  })
}

function agregarValidacionTiempoReal() {
  // Validación de email en tiempo real
  const emailInputs = document.querySelectorAll('input[type="email"]')
  emailInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      const errorId = this.id + "Error"
      if (this.value && !validarFormatoEmail(this.value)) {
        mostrarError(errorId, "Formato de email inválido")
      } else {
        const errorElement = document.getElementById(errorId)
        if (errorElement) {
          errorElement.textContent = ""
          errorElement.style.display = "none"
        }
      }
    })
  })

  // Validación de contraseñas coincidentes
  const confirmPasswordInputs = document.querySelectorAll("#confirm_password, #confirmar_password")
  confirmPasswordInputs.forEach((input) => {
    input.addEventListener("input", function () {
      const passwordInput = document.getElementById("password") || document.getElementById("nueva_password")
      const errorId = this.id + "Error"

      if (this.value && passwordInput.value !== this.value) {
        mostrarError(errorId, "Las contraseñas no coinciden")
      } else {
        const errorElement = document.getElementById(errorId)
        if (errorElement) {
          errorElement.textContent = ""
          errorElement.style.display = "none"
        }
      }
    })
  })
}

// Función para mostrar/ocultar contraseñas
function togglePassword(inputId) {
  const input = document.getElementById(inputId)
  const type = input.getAttribute("type") === "password" ? "text" : "password"
  input.setAttribute("type", type)
}
