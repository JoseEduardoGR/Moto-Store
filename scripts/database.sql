-- Crear base de datos
CREATE DATABASE IF NOT EXISTS moto_store;
USE moto_store;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de motos
CREATE TABLE motos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    año INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    stock INT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    moto_id INT,
    cantidad INT DEFAULT 1,
    total DECIMAL(10,2),
    estado VARCHAR(20) DEFAULT 'pendiente',
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (moto_id) REFERENCES motos(id) ON DELETE CASCADE
);

-- Insertar datos de ejemplo
INSERT INTO usuarios (nombre, email, password, telefono, direccion) VALUES
('Admin', 'admin@motostore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123456789', 'Calle Principal 123'),
('Juan Pérez', 'juan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '987654321', 'Av. Secundaria 456');

INSERT INTO motos (marca, modelo, año, precio, descripcion, imagen, stock) VALUES
('Honda', 'CBR 600RR', 2023, 12500.00, 'Moto deportiva de alta gama con motor de 600cc', 'honda_cbr600.jpg', 5),
('Yamaha', 'YZF-R1', 2023, 18500.00, 'Superbike con tecnología MotoGP', 'yamaha_r1.jpg', 3),
('Kawasaki', 'Ninja ZX-10R', 2023, 16800.00, 'Deportiva de 1000cc con máximo rendimiento', 'kawasaki_ninja.jpg', 4),
('Ducati', 'Panigale V4', 2023, 25000.00, 'Superbike italiana de lujo', 'ducati_panigale.jpg', 2),
('BMW', 'S1000RR', 2023, 19500.00, 'Deportiva alemana con tecnología avanzada', 'bmw_s1000rr.jpg', 3);
