<?php
$patients = $patients ?? [
    ['initials' => 'MJ', 'name' => 'Irribarren', 'Cabudare' => '700', 'status' => 'En progreso', 'status_color' => 'green'],
    ['initials' => 'RP', 'name' => 'Roberto Pérez', 'bmi' => '28.7', 'status' => 'Evaluación', 'status_color' => 'yellow'],
    ['initials' => 'AG', 'name' => 'Ana García', 'bmi' => '22.1', 'status' => 'Completado', 'status_color' => 'green'],
    ['initials' => 'LM', 'name' => 'Luis Martínez', 'bmi' => '26.4', 'status' => 'Nuevo', 'status_color' => 'blue']
];
?>
<div class="bg-white rounded-xl shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold">Entregas Recientes</h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <?php foreach($patients as $patient): ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                        <?php echo $patient['initials']; ?>
                    </div>
                    <div class="ml-4">
                        <p class="font-medium"><?php echo $patient['name']; ?></p>
                        <p class="text-sm text-gray-500">IMC: <?php echo $patient['bmi']; ?></p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-<?php echo $patient['status_color']; ?>-100 text-<?php echo $patient['status_color']; ?>-800 rounded-full text-sm">
                    <?php echo $patient['status']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="w-full mt-4 py-2 text-center text-green-600 hover:bg-green-50 rounded-lg transition duration-200">
            Ver todos los pacientes
        </button>
    </div>
</div>