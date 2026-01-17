<?php
require_once 'config/database.php';
require_once 'includes/security.php';

verificarSesion();

$mensaje = '';
$tipo_mensaje = '';

$database = new Database();
$db = $database->getConnection();

// Obtener datos actuales del usuario
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['usuario_id']);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'])) {
        $mensaje = 'Token de seguridad inválido';
        $tipo_mensaje = 'error';
    } else {
        $nombre = limpiarDatos($_POST['nombre']);
        $email = limpiarDatos($_POST['email']);
        $telefono = limpiarDatos($_POST['telefono']);
        $direccion = limpiarDatos($_POST['direccion']);
        $password_actual = $_POST['password_actual'];
        $nueva_password = $_POST['nueva_password'];
        $confirmar_password = $_POST['confirmar_password'];
        
        // Validaciones básicas
        if (empty($nombre) || empty($email)) {
            $mensaje = 'Nombre y email son obligatorios';
            $tipo_mensaje = 'error';
        } elseif (!validarEmail($email)) {
            $mensaje = 'Email no válido';
            $tipo_mensaje = 'error';
        } else {
            // Verificar si el email ya existe (excepto el actual)
            $query = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $_SESSION['usuario_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Este email ya está en uso por otro usuario';
                $tipo_mensaje = 'error';
            } else {
                $actualizar_password = false;
                $password_hash = '';
                
                // Si se quiere cambiar la contraseña
                if (!empty($nueva_password)) {
                    if (empty($password_actual)) {
                        $mensaje = 'Debes ingresar tu contraseña actual';
                        $tipo_mensaje = 'error';
                    } elseif (!password_verify($password_actual, $usuario['password'])) {
                        $mensaje = 'Contraseña actual incorrecta';
                        $tipo_mensaje = 'error';
                    } elseif ($nueva_password !== $confirmar_password) {
                        $mensaje = 'Las nuevas contraseñas no coinciden';
                        $tipo_mensaje = 'error';
                    } elseif (strlen($nueva_password) < 6) {
                        $mensaje = 'La nueva contraseña debe tener al menos 6 caracteres';
                        $tipo_mensaje = 'error';
                    } else {
                        $actualizar_password = true;
                        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                    }
                }
                
                if (empty($mensaje)) {
                    // Actualizar datos del usuario
                    if ($actualizar_password) {
                        $query = "UPDATE usuarios SET nombre = :nombre, email = :email, telefono = :telefono, direccion = :direccion, password = :password WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':password', $password_hash);
                    } else {
                        $query = "UPDATE usuarios SET nombre = :nombre, email = :email, telefono = :telefono, direccion = :direccion WHERE id = :id";
                        $stmt = $db->prepare($query);
                    }
                    
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':telefono', $telefono);
                    $stmt->bindParam(':direccion', $direccion);
                    $stmt->bindParam(':id', $_SESSION['usuario_id']);
                    
                    if ($stmt->execute()) {
                        $_SESSION['usuario_nombre'] = $nombre;
                        $_SESSION['usuario_email'] = $email;
                        $mensaje = 'Perfil actualizado correctamente';
                        $tipo_mensaje = 'success';
                        
                        // Recargar datos del usuario
                        $query = "SELECT * FROM usuarios WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $_SESSION['usuario_id']);
                        $stmt->execute();
                        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $mensaje = 'Error al actualizar el perfil';
                        $tipo_mensaje = 'error';
                    }
                }
            }
        }
    }
}

$csrf_token = generarTokenCSRF();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Moto Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">Moto Store</h1>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="perfil-container">
        <div class="perfil-form">
            <h2>Mi Perfil</h2>
            
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <form id="perfilForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-section">
                    <h3>Información Personal</h3>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre completo:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        <span class="error-message" id="nombreError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        <span class="error-message" id="emailError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                        <span class="error-message" id="telefonoError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <textarea id="direccion" name="direccion" rows="3"><?php echo htmlspecialchars($usuario['direccion']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Cambiar Contraseña (Opcional)</h3>
                    
                    <div class="form-group">
                        <label for="password_actual">Contraseña actual:</label>
                        <input type="password" id="password_actual" name="password_actual">
                        <span class="error-message" id="passwordActualError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="nueva_password">Nueva contraseña:</label>
                        <input type="password" id="nueva_password" name="nueva_password">
                        <span class="error-message" id="nuevaPasswordError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_password">Confirmar nueva contraseña:</label>
                        <input type="password" id="confirmar_password" name="confirmar_password">
                        <span class="error-message" id="confirmarPasswordError"></span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/validation.js"></script>
</body>
</html>
