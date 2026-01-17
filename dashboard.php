<?php
require_once 'config/database.php';
require_once 'includes/security.php';

verificarSesion();

$database = new Database();
$db = $database->getConnection();

// Obtener datos del usuario
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['usuario_id']);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener motos disponibles
$query = "SELECT * FROM motos WHERE stock > 0 ORDER BY fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$motos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener pedidos del usuario
$query = "SELECT p.*, m.marca, m.modelo FROM pedidos p 
          JOIN motos m ON p.moto_id = m.id 
          WHERE p.usuario_id = :usuario_id 
          ORDER BY p.fecha_pedido DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Moto Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">Moto Store</h1>
            <div class="nav-menu">
                <span class="nav-user">Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></span>
                <a href="perfil.php" class="nav-link">Mi Perfil</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="user-panel">
            <h2>Panel de Usuario</h2>
            <div class="user-info">
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($usuario['telefono']); ?></p>
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($usuario['direccion']); ?></p>
                <p><strong>Miembro desde:</strong> <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></p>
            </div>
        </div>

        <div class="motos-section">
            <h2>Motos Disponibles</h2>
            <div class="motos-grid">
                <?php foreach ($motos as $moto): ?>
                    <div class="moto-card">
                        <div class="moto-image">
                            <img src="images/<?php echo $moto['imagen']; ?>" alt="<?php echo $moto['marca'] . ' ' . $moto['modelo']; ?>" onerror="this.src='images/default-moto.jpg'">
                        </div>
                        <div class="moto-info">
                            <h3><?php echo $moto['marca'] . ' ' . $moto['modelo']; ?></h3>
                            <p class="moto-year">Año: <?php echo $moto['año']; ?></p>
                            <p class="moto-price">$<?php echo number_format($moto['precio'], 2); ?></p>
                            <p class="moto-description"><?php echo htmlspecialchars($moto['descripcion']); ?></p>
                            <p class="moto-stock">Stock: <?php echo $moto['stock']; ?> unidades</p>
                            <button class="btn btn-primary" onclick="realizarPedido(<?php echo $moto['id']; ?>)">
                                Realizar Pedido
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pedidos-section">
            <h2>Mis Pedidos</h2>
            <?php if (count($pedidos) > 0): ?>
                <div class="pedidos-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Moto</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td><?php echo $pedido['id']; ?></td>
                                    <td><?php echo $pedido['marca'] . ' ' . $pedido['modelo']; ?></td>
                                    <td><?php echo $pedido['cantidad']; ?></td>
                                    <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                    <td><span class="estado-<?php echo $pedido['estado']; ?>"><?php echo ucfirst($pedido['estado']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td>
                                        <?php if ($pedido['estado'] == 'pendiente'): ?>
                                            <button class="btn btn-danger btn-small" onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                                                Cancelar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-pedidos">No tienes pedidos realizados.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
