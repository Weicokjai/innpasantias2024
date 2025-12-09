<?php
// Al inicio del archivo
if (!isset($currentUser)) {
    $currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];
}
if (!isset($currentPage)) {
    $currentPage = 'Dashboard';
}
?>
<div class="w-64 bg-green-800 text-white flex flex-col sidebar">
    <div class="p-4 border-b border-green-700 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold">INN</h1>
            <p class="text-sm text-green-200 institute-name">Instituto Nacional de Nutrición</p>
        </div>
        <button id="toggleSidebar" class="toggle-btn text-green-200 hover:text-white p-1 rounded">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="../dashboard/dashboard.php" class="flex items-center p-2 <?php echo $currentPage === 'Dashboard' ? 'bg-green-700' : 'hover:bg-green-700'; ?> rounded-lg">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../../views/beneficiaries/beneficiaries.php" class="flex items-center p-2 <?php echo $currentPage === 'Beneficiarios' ? 'bg-green-700' : 'hover:bg-green-700'; ?> rounded-lg">
                    <i class="fas fa-users mr-3"></i>
                    <span class="sidebar-text">Beneficiarios</span>
                </a>
            </li>
            <li>
                <a href="../../views/benefits/benefits.php" class="flex items-center p-2 <?php echo $currentPage === 'Beneficios' ? 'bg-green-700' : 'hover:bg-green-700'; ?> rounded-lg">
                    <i class="fas fa-gift mr-3"></i>
                    <span class="sidebar-text">Beneficios</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-utensils mr-3"></i>
                    <span class="sidebar-text">Entregados</span>
                </a>
            </li>
            <li>
                <a href="../../views/reportes/reportes.php" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span class="sidebar-text">Reportes</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text">Configuración</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="p-4 border-t border-green-700 flex flex-col gap-4">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="ml-3 user-info">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                <p class="text-xs text-green-200"><?php echo htmlspecialchars($currentUser['role']); ?></p>
            </div>
        </div>
        <!-- Botón de Salir -->
        <button onclick="cerrarSesion()" class="flex items-center justify-center w-full p-2 bg-red-600 hover:bg-red-700 rounded-lg transition duration-200">
            <i class="fas fa-sign-out-alt mr-2"></i>
            <span class="sidebar-text">Cerrar Sesión</span>
        </button>
    </div>
</div>

<script>
// Función para cerrar sesión
function cerrarSesion() {
    if (confirm('¿Está seguro de que desea cerrar sesión?')) {
        // Crear formulario para cerrar sesión
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'logout';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Toggle sidebar
document.getElementById('toggleSidebar').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('sidebar-collapsed');
});

// También puedes agregar la funcionalidad de logout en PHP en tu archivo beneficiaries.php
// Agrega esto en el handleRequest() del controlador:
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
//     session_destroy();
//     header('Location: ../../index.php');
//     exit;
// }
</script>

<style>
.sidebar {
    transition: width 0.3s ease;
}
.sidebar-collapsed {
    width: 5rem !important;
}
.sidebar-collapsed .sidebar-text,
.sidebar-collapsed .user-info,
.sidebar-collapsed .institute-name {
    display: none !important;
}
.toggle-btn {
    transition: transform 0.3s ease;
}
.sidebar-collapsed .toggle-btn {
    transform: rotate(180deg);
}
</style>