<?php
// Si ya está logueado, redirigir al dashboard
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: dashboard/dashboard.php');
    exit;
}

// Procesar el login (sin verificación de credenciales)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['loggedin'] = true;
    $_SESSION['user'] = [
        'name' => 'Dr. Carlos Méndez',
        'role' => 'Nutriólogo',
        'email' => 'carlos.mendez@inn.com'
    ];
    header('Location: views/dashboard/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Instituto Nacional de Nutrición</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Logo y Título -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-heartbeat text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">INN</h1>
            <p class="text-green-600 font-semibold">Instituto Nacional de Nutrición</p>
            <p class="text-gray-600 mt-2">Sistema de Gestión Nutricional</p>
        </div>

        <!-- Card de Login -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Iniciar Sesión</h2>
            <p class="text-gray-600 text-center mb-6">Accede al sistema de gestión</p>
            
            <form method="POST" action="login.php">
                <!-- Campo Email (solo decorativo) -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                    <div class="relative">
                        <input type="email" 
                               value="usuario@inn.com" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-gray-50"
                               readonly>
                        <i class="fas fa-envelope absolute right-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Campo Password (solo decorativo) -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Contraseña</label>
                    <div class="relative">
                        <input type="password" 
                               value="••••••••" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-gray-50"
                               readonly>
                        <i class="fas fa-lock absolute right-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Botón de Login -->
                <button type="submit" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>Acceder al Sistema
                </button>
            </form>

            <!-- Información de demo -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Modo Demo</p>
                        <p class="text-xs text-blue-600">Haz clic en "Acceder al Sistema" para entrar directamente al dashboard.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-gray-500 text-sm">&copy; 2024 Instituto Nacional de Nutrición. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>