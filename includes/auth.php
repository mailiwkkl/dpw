<?php
// includes/auth.php

// Verificar si se debe incluir el archivo de configuración
require_once __DIR__ . '/config.php';  
if (!defined('DB_HOST')) {
    require_once 'config.php';
}
// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    
        // Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'digital_print_fiesta');
define('DB_USER', 'root');
define('DB_PASS', '');
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por teléfono
    $query = "SELECT * FROM usuarios WHERE telefono = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $telefono);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si la cuenta está activa
        if ($user['estado'] != 'Activo') {
            $_SESSION['error'] = "Cuenta desactivada. Contacte al administrador.";
            header("Location: login.php");
            exit();
        }
        
        // Verificar si no tiene contraseña (primera vez)
        if (empty($user['password'])) {
            if (empty($password)) {
                // Primera vez, redirigir a crear contraseña
                $_SESSION['setup_user'] = $user['id'];
                header("Location: setup_password.php");
                exit();
            } else {
                $_SESSION['error'] = "Cuenta sin contraseña configurada. Deje el campo vacío para configurarla.";
                header("Location: login.php");
                exit();
            }
        }
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['user_name'] = $user['nombre_apellidos'];
            
            // Redirigir según el rol
            if ($user['rol'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Contraseña incorrecta.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "No existe una cuenta con ese teléfono.";
        header("Location: login.php");
        exit();
    }
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $nombre_apellidos = trim($_POST['nombre_apellidos']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $direccion = trim($_POST['direccion']);
    $referencia_direccion = trim($_POST['referencia_direccion']);
  // Validaciones básicas
    if (empty($nombre_apellidos) || empty($telefono) || empty($password) || empty($direccion)) {
        $_SESSION['error'] = "Todos los campos obligatorios deben ser completados.";
        header("Location: register.php");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: register.php");
        exit();
    }
    // Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'digital_print_fiesta');
define('DB_USER', 'root');
define('DB_PASS', '');

    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el teléfono ya existe
    $query = "SELECT id FROM usuarios WHERE telefono = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $telefono);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Ya existe una cuenta con este número de teléfono.";
        header("Location: register.php");
        exit();
    }
    
    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $query = "INSERT INTO usuarios (nombre_apellidos, telefono, password, direccion, referencia_direccion, estado) 
              VALUES (?, ?, ?, ?, ?, 'Activo')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $nombre_apellidos);
    $stmt->bindParam(2, $telefono);
    $stmt->bindParam(3, $hashed_password);
    $stmt->bindParam(4, $direccion);
    $stmt->bindParam(5, $referencia_direccion);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Cuenta creada correctamente. Ahora puede iniciar sesión.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al crear la cuenta. Intente nuevamente.";
        header("Location: register.php");
        exit();
    }
}

// Procesar creación de usuario desde admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user_admin'])) {
    if (!isLoggedIn() || !isAdmin()) {
        $_SESSION['error'] = "No tiene permisos para realizar esta acción.";
        header("Location: admin/users.php");
        exit();
    }
    
    $nombre_apellidos = trim($_POST['nombre_apellidos']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $referencia_direccion = trim($_POST['referencia_direccion']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    
    // Validaciones básicas
    if (empty($nombre_apellidos) || empty($telefono) || empty($direccion)) {
        $_SESSION['error'] = "Todos los campos obligatorios deben ser completados.";
        header("Location: admin/users.php");
        exit();
    }
    
    $userData = [
        'nombre_apellidos' => $nombre_apellidos,
        'telefono' => $telefono,
        'direccion' => $direccion,
        'referencia_direccion' => $referencia_direccion,
        'rol' => $rol,
        'estado' => $estado
    ];
    
    $result = createUserFromAdmin($userData);
    
    if ($result['success']) {
        $_SESSION['success'] = "Usuario creado correctamente.";
        
        // Si es cliente, agregar notificación
        if ($rol == 'cliente') {
            addNotification($result['user_id'], "Bienvenido a Digital Print Fiesta. Su cuenta ha sido creada por el administrador. Para activarla, inicie sesión con su teléfono y deje la contraseña en blanco para configurarla.");
        }
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header("Location: admin/users.php");
    exit();
}
?>