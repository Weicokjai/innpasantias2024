<?php
if (!isset($stats)) {
    $stats = [
        'total_Entregas' => ['value' => '1,248', 'change' => '12.5%', 'trend' => 'up'],
        'avg_weight_loss' => ['value' => '3.2 kg', 'change' => '8.2%', 'trend' => 'up'],
        'daily_appointments' => ['value' => '24', 'change' => '2.1%', 'trend' => 'down']
    ];
}
?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <?php foreach($stats as $key => $stat): ?>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-<?php echo $key === 'total Entregas' ? 'users' : ($key === 'avg_weight_loss' ? 'weight' : ($key === 'active_plans' ? 'utensils' : 'calendar-check')); ?> text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">
                    <?php echo $key === 'total_patients' ? 'Total entregas' : ($key === 'avg_weight_loss' ? 'Casos A' : ($key === 'active_plans' ? 'Casos B' : 'Citas del Día')); ?>
                </p>
                <h3 class="text-2xl font-bold"><?php echo $stat['value']; ?></h3>
            </div>
        </div>

    </div>
    <?php endforeach; ?>
</div>