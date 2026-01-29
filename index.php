<?php
session_start();

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simular login exitoso (sin verificar credenciales)
    $_SESSION['loggedin'] = true;
    $_SESSION['user'] = [
        'name' => 'Dr. Carlos Méndez',
        'role' => 'Nutriólogo',
        'email' => 'doctor@inn.com'
    ];
    
    // Redirigir al dashboard
    header('Location: public/views/dashboard/dashboard.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .title-font {
            font-family: 'Poppins', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f0f9ff 0%, #f0fff4 50%, #e6fffa 100%);
        }
        .card-shadow {
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.08), 0 10px 20px rgba(5, 150, 105, 0.06);
        }
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.2);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .pulse-effect {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .wave-bg {
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='20' viewBox='0 0 100 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M21.184 20c.357-.13.72-.264 1.088-.402l1.768-.661C33.64 15.347 39.647 14 50 14c10.271 0 15.362 1.222 24.629 4.928.955.383 1.869.74 2.75 1.072h6.225c-2.51-.73-5.139-1.691-8.233-2.928C65.888 13.278 60.562 12 50 12c-10.626 0-16.855 1.397-26.66 5.063l-1.767.662c-2.475.923-4.66 1.674-6.724 2.275h6.335zm0-20C13.258 2.892 8.077 4 0 4V2c5.744 0 9.951-.574 14.85-2h6.334zM77.38 0C85.239 2.966 90.502 4 100 4V2c-6.842 0-11.386-.542-16.396-2h-6.225zM0 14c8.44 0 13.718-1.21 22.272-4.402l1.768-.661C33.64 5.347 39.647 4 50 4c10.271 0 15.362 1.222 24.629 4.928C84.112 12.722 89.438 14 100 14v-2c-10.271 0-15.362-1.222-24.629-4.928C65.888 3.278 60.562 2 50 2 39.374 2 33.145 3.397 23.34 7.063l-1.767.662C13.223 10.84 8.163 12 0 12v2z' fill='%23059669' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4 wave-bg">
    <div class="max-w-6xl w-full flex flex-col lg:flex-row items-center justify-between gap-8">
        <!-- Panel izquierdo con información -->
        <div class="lg:w-1/2 max-w-lg">
            <div class="mb-8">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg pulse-effect">
                        <i class="fas fa-heartbeat text-white text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="title-font text-4xl font-bold text-gray-900">INN</h1>
                        <p class="text-green-600 font-semibold text-lg">Instituto Nacional de Nutrición</p>
                    </div>
                </div>
                
                <h2 class="title-font text-3xl lg:text-4xl font-bold text-gray-900 leading-tight mb-4">
                    Sistema de <span class="text-green-600">Gestión Nutricional Estadal</span>
                </h2>
                
                <p class="text-gray-600 text-lg mb-8">
                    Plataforma integral para el control, seguimiento y análisis estadístico de pacientes a nivel estadal.
                </p>
            </div>
            
            <!-- Características del sistema -->
            <div class="space-y-6 mb-10">
                <div class="flex items-start">
                    <div class="bg-green-100 p-4 rounded-2xl mr-4 shadow-sm">
                        <i class="fas fa-chart-line text-green-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-lg mb-1">Seguimiento Avanzado</h3>
                        <p class="text-gray-600">Monitoriza el progreso nutricional de pacientes con herramientas analíticas avanzadas y reportes detallados.</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-amber-100 p-4 rounded-2xl mr-4 shadow-sm">
                        <i class="fas fa-file-medical text-amber-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-lg mb-1">Historial Clínico Centralizado</h3>
                        <p class="text-gray-600">Registro completo y unificado de historias médicas con acceso seguro desde cualquier centro de salud estadal.</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-blue-100 p-4 rounded-2xl mr-4 shadow-sm">
                        <i class="fas fa-database text-blue-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-lg mb-1">Registro Completo Estadal</h3>
                        <p class="text-gray-600">Base de datos consolidada con información nutricional de todos los municipios del estado para análisis integral.</p>
                    </div>
                </div>
            </div>
            
            <!-- Información estadística -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-100 rounded-2xl p-6 card-shadow">
                <h4 class="font-bold text-gray-800 text-lg mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-green-600 mr-2"></i> Cobertura Estadal
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-700">9</div>
                        <div class="text-gray-600 text-sm">Municipios</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-700">57</div>
                        <div class="text-gray-600 text-sm">Parroquias</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel derecho con formulario de login -->
        <div class="lg:w-1/2 max-w-md w-full">
            <div class="bg-white rounded-3xl card-shadow overflow-hidden border border-green-50">
                <!-- Encabezado de la tarjeta -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-center">
                    <h2 class="text-2xl font-bold text-white title-font">Acceso al Sistema</h2>
                    <p class="text-green-100 mt-1">Credenciales Estatales Autorizadas</p>
                </div>
                
                <!-- Cuerpo del formulario -->
                <div class="p-8">
                    <form method="POST" action="" class="space-y-6">
                        <!-- Campo Email -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-id-card text-green-500 mr-2"></i>Usuario Institucional
                            </label>
                            <div class="relative">
                                <input type="email" 
                                       class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none input-focus transition duration-200 bg-gray-50 text-gray-700"
                                       value="doctor@inn.gob.ve"
                                       readonly>
                                <div class="absolute right-4 top-4 text-green-500">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <p class="text-gray-500 text-xs mt-2">Usuario oficial del Instituto Nacional de Nutrición</p>
                        </div>

                        <!-- Campo Contraseña -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-key text-green-500 mr-2"></i>Contraseña Segura
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none input-focus transition duration-200 bg-gray-50 text-gray-700"
                                       value="••••••••"
                                       readonly>
                                <div class="absolute right-4 top-4 text-green-500">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            </div>
                            <p class="text-gray-500 text-xs mt-2">Contraseña encriptada de seguridad estadal</p>
                        </div>
                        
                        <!-- Recordar sesión -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" class="h-5 w-5 text-green-600 rounded focus:ring-green-500 border-gray-300">
                                <label for="remember" class="ml-2 text-gray-700">Recordar en este equipo</label>
                            </div>
                            <a href="#" class="text-green-600 hover:text-green-800 font-medium text-sm">
                                <i class="fas fa-question-circle mr-1"></i>Ayuda
                            </a>
                        </div>

                        <!-- Botón de Login -->
                        <button type="submit" 
                                class="btn-primary w-full text-white font-bold py-4 px-4 rounded-xl transition duration-300 transform hover:scale-[1.02] shadow-lg mt-2">
                            <i class="fas fa-sign-in-alt mr-3"></i>Acceso al Sistema Estadal
                        </button>
                        
                        <!-- Separador -->
                        <div class="flex items-center my-6">
                            <div class="flex-grow border-t border-gray-200"></div>
                            <span class="mx-4 text-gray-500 text-sm">
                                <i class="fas fa-lock mr-1"></i>Acceso Seguro
                            </span>
                            <div class="flex-grow border-t border-gray-200"></div>
                        </div>
                    </form>
                </div>
                
                <!-- Pie de la tarjeta -->
                <div class="bg-gray-50 border-t border-gray-100 p-6 text-center">
                    <p class="text-gray-600 text-sm">
                        <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>
                        Uso exclusivo para personal autorizado del 
                        <span class="font-semibold text-green-700">INN Estadal</span>
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-gray-500 text-sm">
                    &copy; 2026 Instituto Nacional de Nutrición - Dirección Estadal
                    <span class="block mt-1">Sistema de Gestión Nutricional v4.1 Estadal</span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Elementos decorativos adicionales -->
    <div class="hidden lg:block">
        <div class="absolute top-10 left-10 w-24 h-24 bg-green-100 rounded-full opacity-20 animate-float"></div>
        <div class="absolute bottom-10 right-10 w-32 h-32 bg-emerald-100 rounded-full opacity-30 animate-float" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/3 right-1/4 w-16 h-16 bg-blue-100 rounded-full opacity-20 animate-float" style="animation-delay: 2s;"></div>
    </div>
</body>
</html>