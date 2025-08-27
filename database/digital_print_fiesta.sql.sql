-- database/digital_print_fiesta.sql
CREATE DATABASE IF NOT EXISTS digital_print_fiesta;
USE digital_print_fiesta;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    direccion TEXT NOT NULL,
    referencia_direccion TEXT,
    rol ENUM('admin', 'cliente') DEFAULT 'cliente',
    estado ENUM('Activo', 'Desactivado') DEFAULT 'Desactivado',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de promociones
CREATE TABLE promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255) NOT NULL,
    activa TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar algunas promociones de ejemplo
INSERT INTO promociones (titulo, descripcion, imagen, orden) VALUES
('20% OFF en Invitaciones', 'Descuento especial en todas nuestras invitaciones', 'promo1.webp', 1),
('Tarjetas de Presentación x2', 'Lleva el doble por el mismo precio', 'promo2.webp', 2),
('Posters A3 a Precio Especial', 'Posters de alta calidad a precio especial', 'promo3.webp', 3);

-- Tabla de temáticas
CREATE TABLE tematicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen VARCHAR(255) NOT NULL
);

-- Tabla de categorías (subcategorías)
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tematica_id INT NOT NULL,
    FOREIGN KEY (tematica_id) REFERENCES tematicas(id) ON DELETE CASCADE
);

-- Tabla de galería
CREATE TABLE galeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tematica_id INT NOT NULL,
    categoria_id INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    visualizaciones INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tematica_id) REFERENCES tematicas(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Tabla de servicios
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
);

-- Tabla de facturas
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega DATE,
    cliente_id INT NOT NULL,
    direccion TEXT NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    importe_total DECIMAL(10,2) DEFAULT 0,
    pagado DECIMAL(10,2) DEFAULT 0,
    estado ENUM('Solicitando', 'En Revisión', 'Rechazada', 'Pendiente de Pago', 'En Espera', 'En Proceso', 'Terminada', 'Entregada') DEFAULT 'Solicitando',
    detalles TEXT,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_id INT,
    galeria_id INT,
    detalles TEXT,
    precio DECIMAL(10,2) NOT NULL,
    copias INT DEFAULT 1,
    descuento DECIMAL(10,2) DEFAULT 0,
    importe DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL,
    FOREIGN KEY (galeria_id) REFERENCES galeria(id) ON DELETE SET NULL
);

-- Tabla de favoritos
CREATE TABLE favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    galeria_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (galeria_id) REFERENCES galeria(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (usuario_id, galeria_id)
);

-- Tabla de notificaciones
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enlace VARCHAR(255),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Insertar usuario admin por defecto (contraseña: admin123)
INSERT INTO usuarios (nombre_apellidos, telefono, password, direccion, rol, estado) 
VALUES ('Administrador', '000000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dirección admin', 'admin', 'Activo');

-- Insertar algunos servicios básicos
INSERT INTO servicios (servicio, precio) VALUES
('Impresión A4', 2.50),
('Impresión A3', 4.50),
('Tarjetas de Presentación', 15.00),
('Folletos', 30.00),
('Posters', 25.00);

-- Insertar algunas temáticas
INSERT INTO tematicas (nombre, imagen) VALUES
('Cumpleaños', 'T1.webp'),
('Bodas', 'T2.webp'),
('Empresarial', 'T3.webp'),
('Infantil', 'T4.webp');

-- Insertar algunas categorías
INSERT INTO categorias (nombre, tematica_id) VALUES
('Invitaciones', 1),
('Decoración', 1),
('Recuerdos', 2),
('Invitaciones', 2),
('Logos', 3),
('Tarjetas', 3),
('Juegos', 4),
('Educativo', 4);