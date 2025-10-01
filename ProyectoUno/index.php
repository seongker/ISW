<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styleIndex.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Bienvenido</h2>
            <p>Ingresa a tu cuenta para continuar</p>
        </div>
        
        <?php
        session_start();
        // Mostrar mensajes de error específicos si existen
        if (isset($_SESSION['error_username'])) {
            echo '<div class="error-message-global" id="usernameErrorGlobal">' . $_SESSION['error_username'] . '</div>';
            unset($_SESSION['error_username']);
        }
        
        if (isset($_SESSION['error_password'])) {
            echo '<div class="error-message-global" id="passwordErrorGlobal">' . $_SESSION['error_password'] . '</div>';
            unset($_SESSION['error_password']);
        }
        
        if (isset($_SESSION['error_login'])) {
            echo '<div class="error-message-global">' . $_SESSION['error_login'] . '</div>';
            unset($_SESSION['error_login']);
        }
        ?>
        
        <form class="login-form" id="loginForm" name="loginForm" action="ProcesarLogin.php" method="post" target="_self">
            <div class="form-group">
                <label for="username">Número de control</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Ingresa tu número de control" 
                           maxlength="8" readonly>
                </div>
                <div class="character-count" id="usernameCount">0/8</div>
                <div class="error-message" id="usernameError">Por favor ingresa un número de control válido</div>
            </div>
            
            <div class="form-group">
                <label for="password">Ingresa tu NIP</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="text" id="password" name="password" placeholder="Ingresa tu NIP" 
                           maxlength="4" readonly>
                </div>
                <div class="character-count" id="passwordCount">0/4</div>
                <div class="error-message" id="passwordError">Por favor ingresa un NIP válido</div>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
    </div>

    <!-- Teclado numérico virtual -->
    <div class="numeric-keypad-container" id="numericKeypad">
        <div class="numeric-keypad">
            <button type="button" data-value="1">1</button>
            <button type="button" data-value="2">2</button>
            <button type="button" data-value="3">3</button>
            <button type="button" data-value="4">4</button>
            <button type="button" data-value="5">5</button>
            <button type="button" data-value="6">6</button>
            <button type="button" data-value="7">7</button>
            <button type="button" data-value="8">8</button>
            <button type="button" data-value="9">9</button>
            <button type="button" data-value="0">0</button>
        </div>
        
        <div class="keypad-controls">
            <button type="button" class="keypad-clear" id="keypadClear">Limpiar</button>
            <button type="button" class="keypad-backspace" id="keypadBackspace">⌫</button>
            <button type="button" class="keypad-close" id="keypadClose">Cerrar</button>
        </div>
    </div>

    <script src="js/scriptIndex.js"></script>
</body>
</html>