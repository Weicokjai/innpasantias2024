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
                <a href="../../views/beneficiaries/beneficiaries.php" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
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
                <a href="#" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
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
    <div class="p-4 border-t border-green-700">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="ml-3 user-info">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                <p class="text-xs text-green-200"><?php echo htmlspecialchars($currentUser['role']); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('toggleSidebar').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('sidebar-collapsed');
});
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