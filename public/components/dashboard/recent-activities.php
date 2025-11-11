<?php
$activities = $activities ?? [
    [
        'icon' => 'weight',
        'color' => 'green',
        'title' => 'Nuevo registro de peso',
        'description' => 'María Jiménez perdió 1.2kg esta semana',
        'time' => 'Hace 2 horas'
    ],
    [
        'icon' => 'user-plus', 
        'color' => 'blue',
        'title' => 'Nuevo paciente',
        'description' => 'Luis Martínez se registró en el sistema',
        'time' => 'Hace 5 horas'
    ],
    [
        'icon' => 'utensils',
        'color' => 'yellow', 
        'title' => 'Plan nutricional actualizado',
        'description' => 'Roberto Pérez recibió nuevo plan de alimentación',
        'time' => 'Ayer a las 14:30'
    ]
];
?>
<div class="bg-white rounded-xl shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold">Actividad Reciente</h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <?php foreach($activities as $activity): ?>
            <div class="flex">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-10 h-10 rounded-full bg-<?php echo $activity['color']; ?>-100 flex items-center justify-center text-<?php echo $activity['color']; ?>-600">
                        <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                    </div>
                </div>
                <div>
                    <p class="font-medium"><?php echo $activity['title']; ?></p>
                    <p class="text-sm text-gray-500"><?php echo $activity['description']; ?></p>
                    <p class="text-xs text-gray-400"><?php echo $activity['time']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>