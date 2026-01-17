<?php
require_once 'config/database.php';
require_once 'includes/security.php';

$mensaje = '';
$tipo_mensaje = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'acceso_denegado':
            $mensaje = 'Debes iniciar sesión para acceder a esta página';
            $tipo_mensaje = 'error';
            break;
        case 'sesion_cerrada':
            $mensaje = 'Sesión cerrada correctamente';
            $tipo_mensaje = 'success';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = limpiarDatos($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $mensaje = 'Por favor, completa todos los campos';
        $tipo_mensaje = 'error';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, nombre, email, password FROM usuarios WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $usuario['password'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email'] = $usuario['email'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $mensaje = 'Credenciales incorrectas';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Usuario no encontrado';
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Moto Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Iniciar Sesión</h2>
            
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <span class="error-message" id="emailError"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                    <span class="error-message" id="passwordError"></span>
                </div>
                
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </form>
            
            <p class="register-link">
                ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
            </p>
        </div>
    </div>
    
    <script src="js/validation.js"></script>
</body>
</html>
