<?php
require_once 'config/database.php';
require_once 'includes/security.php';

verificarSesion();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion'] ?? '';

$database = new Database();
$db = $database->getConnection();

try {
    if ($accion == 'realizar_pedido') {
        $moto_id = $input['moto_id'] ?? 0;
        $cantidad = $input['cantidad'] ?? 1;
        
        // Verificar que la moto existe y tiene stock
        $query = "SELECT * FROM motos WHERE id = :id AND stock >= :cantidad";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $moto_id);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Moto no disponible o stock insuficiente']);
            exit();
        }
        
        $moto = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $moto['precio'] * $cantidad;
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Crear pedido
        $query = "INSERT INTO pedidos (usuario_id, moto_id, cantidad, total) VALUES (:usuario_id, :moto_id, :cantidad, :total)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
        $stmt->bindParam(':moto_id', $moto_id);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':total', $total);
        $stmt->execute();
        
        // Actualizar stock
        $query = "UPDATE motos SET stock = stock - :cantidad WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':id', $moto_id);
        $stmt->execute();
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Pedido realizado correctamente']);
        
    } elseif ($accion == 'cancelar_pedido') {
        $pedido_id = $input['pedido_id'] ?? 0;
        
        // Verificar que el pedido pertenece al usuario y está pendiente
        $query = "SELECT * FROM pedidos WHERE id = :id AND usuario_id = :usuario_id AND estado = 'pendiente'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $pedido_id);
        $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o no se puede cancelar']);
            exit();
        }
        
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Eliminar pedido (DELETE)
        $query = "DELETE FROM pedidos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $pedido_id);
        $stmt->execute();
        
        // Restaurar stock
        $query = "UPDATE motos SET stock = stock + :cantidad WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cantidad', $pedido['cantidad']);
        $stmt->bindParam(':id', $pedido['moto_id']);
        $stmt->execute();
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Pedido cancelado correctamente']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
