document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');
    const loginButton = document.querySelector('.btn-login');
    const numericKeypad = document.getElementById('numericKeypad');
    const keypadClear = document.getElementById('keypadClear');
    const keypadBackspace = document.getElementById('keypadBackspace');
    const keypadClose = document.getElementById('keypadClose');
    const keypadButtons = document.querySelectorAll('.numeric-keypad button');
    const body = document.body;
    
    let activeInput = null;
    
    // Inicializar contadores de caracteres
    updateCharacterCount('username', 8);
    updateCharacterCount('password', 4);
    
    // Mostrar teclado al hacer clic en los campos
    usernameInput.addEventListener('click', function() {
        activeInput = usernameInput;
        showKeypad();
        highlightActiveInput();
        adjustBodyPosition();
    });
    
    passwordInput.addEventListener('click', function() {
        activeInput = passwordInput;
        showKeypad();
        highlightActiveInput();
        adjustBodyPosition();
    });
    
    // Cerrar teclado al hacer clic en el botón de cerrar
    keypadClose.addEventListener('click', function() {
        hideKeypad();
        resetBodyPosition();
    });
    
    // Limpiar campo activo
    keypadClear.addEventListener('click', function() {
        if (activeInput) {
            activeInput.value = '';
            updateCharacterCount(activeInput.id, activeInput.id === 'username' ? 8 : 4);
            validateField(activeInput);
        }
    });
    
    // Borrar último carácter
    keypadBackspace.addEventListener('click', function() {
        if (activeInput) {
            activeInput.value = activeInput.value.slice(0, -1);
            updateCharacterCount(activeInput.id, activeInput.id === 'username' ? 8 : 4);
            validateField(activeInput);
        }
    });
    
    // Añadir dígitos al campo activo
    keypadButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (activeInput) {
                const value = this.getAttribute('data-value');
                const maxLength = activeInput.id === 'username' ? 8 : 4;
                
                if (activeInput.value.length < maxLength) {
                    activeInput.value += value;
                    updateCharacterCount(activeInput.id, maxLength);
                    validateField(activeInput);
                }
            }
        });
    });
    
    // Manejar el evento de envío del formulario
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Validar nombre de usuario
        if (!validateField(usernameInput)) {
            isValid = false;
        }
        
        // Validar contraseña
        if (!validateField(passwordInput)) {
            isValid = false;
        }
        
        // Si todo es válido, proceder con el "inicio de sesión"
        if (isValid) {
            loginButton.textContent = 'Iniciando sesión...';
            loginButton.disabled = true;
            
            // Enviar el formulario
            const formData = new FormData(loginForm);
            
            fetch('ProcesarLogin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Verificar si la respuesta es JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    return response.json();
                } else {
                    // Si no es JSON, obtener el texto para debugging
                    return response.text().then(text => {
                        throw new TypeError("La respuesta no es JSON. Recibido: " + text.substring(0, 100));
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    // Login exitoso - redirigir
                    window.location.href = data.redirect || 'DashboardPrincipal.php';
                } else {
                    // Mostrar errores específicos
                    if (data.error_type === 'username') {
                        showGlobalError('usernameErrorGlobal', data.message);
                    } else if (data.error_type === 'password') {
                        showGlobalError('passwordErrorGlobal', data.message);
                    } else {
                        showGlobalError('generalError', data.message);
                    }
                    
                    loginButton.textContent = 'Iniciar Sesión';
                    loginButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showGlobalError('generalError', 'Error de conexión. Intenta nuevamente.');
                loginButton.textContent = 'Iniciar Sesión';
                loginButton.disabled = false;
            });
        } // <-- Esta es la llave que faltaba para cerrar el if (isValid)
    }); // <-- Esta es la llave que cierra el evento submit

    // Función para mostrar el teclado
    function showKeypad() {
        numericKeypad.classList.add('visible');
    }
    
    // Función para ocultar el teclado
    function hideKeypad() {
        numericKeypad.classList.remove('visible');
        if (activeInput) {
            activeInput.classList.remove('input-active');
            activeInput = null;
        }
    }
    
    // Función para ajustar la posición del body cuando se muestra el teclado
    function adjustBodyPosition() {
        body.classList.add('keyboard-visible');
        
        // Desplazar suavemente hacia arriba
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // Función para restaurar la posición del body cuando se oculta el teclado
    function resetBodyPosition() {
        body.classList.remove('keyboard-visible');
    }
    
    // Función para resaltar el campo activo
    function highlightActiveInput() {
        // Quitar resaltado de todos los campos
        usernameInput.classList.remove('input-active');
        passwordInput.classList.remove('input-active');
        
        // Resaltar el campo activo
        if (activeInput) {
            activeInput.classList.add('input-active');
        }
    }
    
    // Función para validar campos
    function validateField(field) {
        const value = field.value.trim();
        const errorElement = document.getElementById(field.id + 'Error');
        const maxLength = field.id === 'username' ? 8 : 4;
        const fieldName = field.id === 'username' ? 'número de control' : 'NIP';
        
        if (value === '') {
            errorElement.textContent = `Por favor ingresa tu ${fieldName}`;
            errorElement.style.display = 'block';
            field.classList.add('input-error');
            field.classList.remove('input-success');
            return false;
        } else if (value.length < maxLength) {
            errorElement.textContent = `El ${fieldName} debe tener ${maxLength} dígitos`;
            errorElement.style.display = 'block';
            field.classList.add('input-error');
            field.classList.remove('input-success');
            return false;
        } else {
            errorElement.style.display = 'none';
            field.classList.remove('input-error');
            field.classList.add('input-success');
            return true;
        }
    }
    
    // Función para actualizar el contador de caracteres
    function updateCharacterCount(fieldId, maxLength) {
        const field = document.getElementById(fieldId);
        const countElement = document.getElementById(fieldId + 'Count');
        const currentLength = field.value.length;
        
        countElement.textContent = `${currentLength}/${maxLength}`;
        
        if (currentLength >= maxLength) {
            countElement.classList.add('max-reached');
        } else {
            countElement.classList.remove('max-reached');
        }
    }
    
    // Cerrar el teclado al hacer clic fuera de él
    document.addEventListener('click', function(e) {
        if (numericKeypad.classList.contains('visible') && 
            !numericKeypad.contains(e.target) && 
            e.target !== usernameInput && 
            e.target !== passwordInput) {
            hideKeypad();
            resetBodyPosition();
        }
    });

    // Función para mostrar errores 
    function showGlobalError(elementId, message) {
        // Eliminar cualquier error existente
        const existingErrors = document.querySelectorAll('.error-message-global');
        existingErrors.forEach(error => error.remove());
        
        // Crear nuevo elemento de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message-global';
        errorDiv.id = elementId;
        errorDiv.textContent = message;
        
        // Insertar después del encabezado de login
        const loginHeader = document.querySelector('.login-header');
        loginHeader.parentNode.insertBefore(errorDiv, loginHeader.nextSibling);
        
        // Hacer scroll al error
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}); // <-- Esta llave cierra el DOMContentLoaded