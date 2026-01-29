<?php
// Al inicio del archivo
if (!isset($currentUser)) {
    $currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];
}
if (!isset($currentPage)) {
    $currentPage = 'Dashboard';
}
?>

<div class="w-64 bg-gradient-to-b from-green-900 to-green-800 text-white flex flex-col sidebar shadow-2xl relative" style="background: linear-gradient(135deg, #14532d 0%, #0f4229 100%)">
    <!-- Logo y Toggle -->
    <div class="p-5 border-b border-green-700 flex justify-between items-center" style="background: #0f4229">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center shadow-lg">
                <span class="font-bold text-lg">INN</span>
            </div>
            <div>
                <p class="text-xs text-green-300 institute-name mt-0.5">Instituto Nacional de Nutrición</p>
            </div>
        </div>
        <button id="toggleSidebar" class="toggle-btn text-green-300 hover:text-white p-2 rounded-full" style="background: #14532d">
            <i class="fas fa-chevron-left text-sm"></i>
        </button>
    </div>

    <!-- Menú de Navegación -->
    <nav class="flex-1 p-4 overflow-y-auto">
        <p class="text-xs uppercase tracking-wider text-green-400 mb-4 px-3 sidebar-text font-semibold">Navegación Principal</p>
        <ul class="space-y-2">
            <li>
                <a href="../dashboard/dashboard.php" class="flex items-center p-3 <?php echo $currentPage === 'Dashboard' ? 'bg-gradient-to-r from-green-700 to-green-600 shadow-lg border-l-4 border-green-400' : 'hover:bg-green-800 hover:shadow-md hover:border-l-4 hover:border-green-500'; ?> rounded-xl transition-all duration-300 group">
                    <div class="w-8 h-8 rounded-lg bg-green-700 flex items-center justify-center mr-3 group-hover:bg-green-600 transition-colors">
                        <i class="fas fa-chart-line text-sm <?php echo $currentPage === 'Dashboard' ? 'text-green-300' : 'text-green-400'; ?>"></i>
                    </div>
                    <span class="sidebar-text font-medium tracking-wide">Dashboard</span>
                    <?php if($currentPage === 'Dashboard'): ?>
                    <span class="ml-auto animate-pulse">
                        <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../../views/beneficiaries/beneficiaries.php" class="flex items-center p-3 <?php echo $currentPage === 'Beneficiarios' ? 'bg-gradient-to-r from-green-700 to-green-600 shadow-lg border-l-4 border-green-400' : 'hover:bg-green-800 hover:shadow-md hover:border-l-4 hover:border-green-500'; ?> rounded-xl transition-all duration-300 group">
                    <div class="w-8 h-8 rounded-lg bg-green-700 flex items-center justify-center mr-3 group-hover:bg-green-600 transition-colors">
                        <i class="fas fa-users text-sm <?php echo $currentPage === 'Beneficiarios' ? 'text-green-300' : 'text-green-400'; ?>"></i>
                    </div>
                    <span class="sidebar-text font-medium tracking-wide">Beneficiarios</span>
                    <?php if($currentPage === 'Beneficiarios'): ?>
                    <span class="ml-auto animate-pulse">
                        <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../../views/benefits/benefits.php" class="flex items-center p-3 <?php echo $currentPage === 'Beneficios' ? 'bg-gradient-to-r from-green-700 to-green-600 shadow-lg border-l-4 border-green-400' : 'hover:bg-green-800 hover:shadow-md hover:border-l-4 hover:border-green-500'; ?> rounded-xl transition-all duration-300 group">
                    <div class="w-8 h-8 rounded-lg bg-green-700 flex items-center justify-center mr-3 group-hover:bg-green-600 transition-colors">
                        <i class="fas fa-gift text-sm <?php echo $currentPage === 'Beneficios' ? 'text-green-300' : 'text-green-400'; ?>"></i>
                    </div>
                    <span class="sidebar-text font-medium tracking-wide">Beneficios</span>
                    <?php if($currentPage === 'Beneficios'): ?>
                    <span class="ml-auto animate-pulse">
                        <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../../views/reportes/reportes.php" class="flex items-center p-3 <?php echo $currentPage === 'Reportes' ? 'bg-gradient-to-r from-green-700 to-green-600 shadow-lg border-l-4 border-green-400' : 'hover:bg-green-800 hover:shadow-md hover:border-l-4 hover:border-green-500'; ?> rounded-xl transition-all duration-300 group">
                    <div class="w-8 h-8 rounded-lg bg-green-700 flex items-center justify-center mr-3 group-hover:bg-green-600 transition-colors">
                        <i class="fas fa-chart-bar text-sm <?php echo $currentPage === 'Reportes' ? 'text-green-300' : 'text-green-400'; ?>"></i>
                    </div>
                    <span class="sidebar-text font-medium tracking-wide">Reportes</span>
                    <?php if($currentPage === 'Reportes'): ?>
                    <span class="ml-auto animate-pulse">
                        <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Perfil y Cerrar Sesión -->
    <div class="p-4 border-t border-green-700 flex flex-col gap-4" style="background: #0f4229">
        <!-- Perfil de Usuario -->
        <div class="flex items-center p-3 bg-gradient-to-r from-green-800 to-green-700 rounded-xl hover:from-green-700 hover:to-green-600 transition-all duration-300 cursor-pointer group">
            <div class="relative">
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-user-md text-white text-lg"></i>
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-green-800"></div>
            </div>
            <div class="ml-3 user-info">
                <p class="text-sm font-semibold tracking-wide"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                <p class="text-xs text-green-300 flex items-center mt-0.5">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                    <?php echo htmlspecialchars($currentUser['role']); ?>
                </p>
            </div>
        </div>

        <!-- Botón de Cerrar Sesión -->
        <button onclick="cerrarSesion()" class="flex items-center justify-center w-full p-3.5 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 shadow-lg hover:shadow-red-500/30 rounded-xl transition-all duration-300 group relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
            <i class="fas fa-sign-out-alt mr-3 text-white group-hover:animate-pulse"></i>
            <span class="sidebar-text font-semibold tracking-wide">Cerrar Sesión</span>
        </button>

        <!-- Versión del sistema -->
        <div class="text-center pt-2">
            <p class="text-xs text-green-400 sidebar-text">v2.1.0 • Sistema Nutrición</p>
        </div>
    </div>

    <!-- Indicador de estado -->
    <div class="absolute top-4 right-4 w-3 h-3 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></div>
</div>

<script>
// Función para cerrar sesión
function cerrarSesion() {
    // Verificar si SweetAlert2 está disponible
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Cerrar Sesión?',
            text: "¿Está seguro de que desea salir del sistema?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar',
            background: '#1a202c',
            color: '#fff',
            backdrop: 'rgba(0,0,0,0.8)'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loader
                Swal.fire({
                    title: 'Cerrando sesión...',
                    text: 'Redirigiendo al login',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Redirigir después de 1.5 segundos
                setTimeout(() => {
                    window.location.href = '../../../index.php';
                }, 1500);
            }
        });
    } else {
        // Fallback al confirm nativo si SweetAlert2 no está cargado
        if (confirm('¿Está seguro de que desea cerrar sesión?')) {
            window.location.href = '../../../index.php';
        }
    }
}

// Toggle sidebar con animación mejorada
document.getElementById('toggleSidebar').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleIcon = this.querySelector('i');
    
    sidebar.classList.toggle('sidebar-collapsed');
    
    // Cambiar icono
    if (sidebar.classList.contains('sidebar-collapsed')) {
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
    } else {
        toggleIcon.classList.remove('fa-chevron-right');
        toggleIcon.classList.add('fa-chevron-left');
    }
    
    // Guardar estado en localStorage
    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
});

// Cargar estado del sidebar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleIcon = document.querySelector('#toggleSidebar i');
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isCollapsed) {
        sidebar.classList.add('sidebar-collapsed');
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
    }
    
    // Asegurar que todo sea visible (eliminar transparencias forzadamente)
    document.querySelectorAll('.sidebar *').forEach(element => {
        // Eliminar cualquier opacidad
        const computedStyle = window.getComputedStyle(element);
        if (computedStyle.opacity < 1) {
            element.style.opacity = '1';
        }
    });
});

// Efecto hover en elementos del menú
document.querySelectorAll('nav a').forEach(item => {
    item.addEventListener('mouseenter', function() {
        if (!this.classList.contains('bg-gradient-to-r')) {
            this.style.transform = 'translateX(5px)';
        }
    });
    
    item.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});
</script>

<style>
.sidebar {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    min-height: 100vh;
    height: 100%;
    box-shadow: 8px 0 25px rgba(0, 0, 0, 0.4);
    z-index: 50;
    background: linear-gradient(135deg, #14532d 0%, #0f4229 100%) !important;
}

/* Fuerza opacidad completa */
.sidebar * {
    opacity: 1 !important;
}

.sidebar-collapsed {
    width: 80px !important;
}

.sidebar-collapsed .sidebar-text,
.sidebar-collapsed .user-info,
.sidebar-collapsed .institute-name {
    display: none !important;
    opacity: 0;
    transform: translateX(-20px);
}

.sidebar-collapsed nav p {
    font-size: 0;
    padding: 0;
    margin: 0;
    height: 0;
    overflow: hidden;
}

.sidebar-collapsed .bg-green-800 {
    display: none;
}

.sidebar-collapsed .text-center {
    display: none;
}

.toggle-btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 0 15px rgba(74, 222, 128, 0.4);
}

/* Animaciones para elementos del menú */
nav a {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

nav a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
}

nav a:hover::before {
    left: 100%;
}

/* Scroll personalizado */
.sidebar nav {
    scrollbar-width: thin;
    scrollbar-color: #4ade80 #14532d;
}

.sidebar nav::-webkit-scrollbar {
    width: 5px;
}

.sidebar nav::-webkit-scrollbar-track {
    background: #14532d;
    border-radius: 10px;
}

.sidebar nav::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #4ade80, #16a34a);
    border-radius: 10px;
}

.sidebar nav::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #22c55e, #15803d);
}

/* Asegurar que los textos sean visibles */
.sidebar-text,
.user-info,
.institute-name,
.text-green-300,
.text-green-400,
.text-green-200 {
    opacity: 1 !important;
    color: inherit !important;
}

/* Asegurar que los iconos sean visibles */
.fa-chart-line,
.fa-users,
.fa-gift,
.fa-chart-bar,
.fa-user-md,
.fa-sign-out-alt,
.fa-chevron-left,
.fa-chevron-right {
    opacity: 1 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 80px !important;
    }
    
    .sidebar-text,
    .user-info,
    .institute-name {
        display: none !important;
    }
    
    .sidebar:not(.sidebar-collapsed) {
        width: 280px !important;
        position: fixed;
        z-index: 1000;
        height: 100vh;
    }
}

/* Animación para el botón de cerrar sesión */
@keyframes pulse {
    0% { opacity: 0.8; }
    50% { opacity: 1; }
    100% { opacity: 0.8; }
}

.group:hover .fa-sign-out-alt {
    animation: pulse 0.5s ease-in-out infinite;
}

/* Eliminar todas las transparencias */
.bg-green-900\/60,
.bg-green-800\/50,
.bg-green-700\/30,
.bg-green-800\/30,
.bg-green-900\/40,
.from-green-800\/40,
.to-green-700\/30,
.from-green-700\/90,
.to-green-600\/90,
.bg-green-800\/50,
.hover\:bg-green-800\/50,
.bg-green-700\/30,
.group-hover\:bg-green-600\/50,
.bg-green-900\/40,
.hover\:from-green-700\/50,
.hover\:to-green-600\/40,
.bg-gradient-to-r.from-green-700\/90,
.bg-gradient-to-r.to-green-600\/90 {
    opacity: 1 !important;
    background-color: inherit !important;
    background-image: none !important;
}

/* Reemplazar colores con opacidad por colores sólidos */
div[class*="bg-green-900/"],
div[class*="bg-green-800/"],
div[class*="bg-green-700/"] {
    background-color: inherit !important;
    opacity: 1 !important;
}
</style>

<!-- Incluir SweetAlert2 para mejores alertas -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>