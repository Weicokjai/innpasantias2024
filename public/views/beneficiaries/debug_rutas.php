<?php
echo "<h3>Debug de rutas CSS</h3>";

// Rutas a verificar
$rutas = [
    '1. ../../assets/css/output.css' => realpath(__DIR__ . '/../../assets/css/output.css'),
    '2. ../../../assets/css/output.css' => realpath(__DIR__ . '/../../../assets/css/output.css'),
    '3. ../../../../assets/css/output.css' => realpath(__DIR__ . '/../../../../assets/css/output.css'),
    '4. /assets/css/output.css' => realpath($_SERVER['DOCUMENT_ROOT'] . '/assets/css/output.css'),
    '5. /innprojec/public/assets/css/output.css' => realpath($_SERVER['DOCUMENT_ROOT'] . '/innprojec/public/assets/css/output.css')
];

foreach($rutas as $label => $ruta) {
    $existe = $ruta && file_exists($ruta) ? '✅ EXISTE' : '❌ NO EXISTE';
    echo "<p><strong>$label</strong> - $existe</p>";
    if ($ruta) {
        echo "<p style='margin-left: 20px; color: #666;'>Ruta real: $ruta</p>";
    }
}

echo "<h3>Estructura de carpetas:</h3>";
echo "<pre>";
echo "Directorio actual: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";
?>