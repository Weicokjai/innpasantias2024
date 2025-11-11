// Esperar a que el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard JS cargado correctamente'); // Para debug
    
    // BMI Distribution Chart
    const bmiCtx = document.getElementById('bmiChart');
    if (bmiCtx) {
        console.log('Creando gráfico IMC');
        new Chart(bmiCtx, {
            type: 'bar',
            data: {
                labels: ['Bajo peso', 'Normal', 'Sobrepeso', 'Obesidad I', 'Obesidad II', 'Obesidad III'],
                datasets: [{
                    label: 'Número de Pacientes',
                    data: [45, 320, 420, 280, 120, 63],
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } else {
        console.log('No se encontró bmiChart');
    }

    // Nutrition Goals Chart
    const goalsCtx = document.getElementById('goalsChart');
    if (goalsCtx) {
        console.log('Creando gráfico de objetivos');
        new Chart(goalsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Alcanzadas', 'En progreso', 'No alcanzadas'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 205, 86, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    } else {
        console.log('No se encontró goalsChart');
    }
});