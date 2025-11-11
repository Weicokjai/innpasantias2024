<?php
// Al inicio del archivo
if (!isset($currentUser)) {
    $currentUser = ['name' => 'Dr. Carlos Méndez', 'role' => 'Nutriólogo'];
}
if (!isset($currentPage)) {
    $currentPage = 'Dashboard';
}
?>
<div class="w-64 bg-green-800 text-white flex flex-col">
    <div class="p-4 border-b border-green-700">
        <h1 class="text-xl font-bold">INN</h1>
        <p class="text-sm text-green-200">Instituto Nacional de Nutrición</p>
    </div>
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="../dashboard/dashboard.php" class="flex items-center p-2 <?php echo $currentPage === 'Dashboard' ? 'bg-green-700' : 'hover:bg-green-700'; ?> rounded-lg">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../../views/beneficiaries/beneficiaries.php" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-users mr-3"></i>
                    <span>Beneficiarios</span>
                </a>
            </li>
            <li>
                <a href="../../views/benefits/benefits.php" class="flex items-center p-2 <?php echo $currentPage === 'Beneficios' ? 'bg-green-700' : 'hover:bg-green-700'; ?> rounded-lg">
                    <i class="fas fa-gift mr-3"></i>
                    <span>Beneficios</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-utensils mr-3"></i>
                    <span>Entregados</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center p-2 hover:bg-green-700 rounded-lg">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="p-4 border-t border-green-700">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                <p class="text-xs text-green-200"><?php echo htmlspecialchars($currentUser['role']); ?></p>
            </div>
        </div>
    </div>
</div>