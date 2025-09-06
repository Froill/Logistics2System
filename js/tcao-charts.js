<script>
const tripLabels = <?= json_encode(array_column($joinedData, 'trip_id')) ?>;
const costData = <?= json_encode(array_column($joinedData, 'total_cost')) ?>;
const fuelData = <?= json_encode(array_column($joinedData, 'fuel_cost')) ?>;
const tollData = <?= json_encode(array_column($joinedData, 'toll_fees')) ?>;
const otherData = <?= json_encode(array_column($joinedData, 'other_expenses')) ?>;

// Derived metrics
const distanceData = <?= json_encode(array_column($joinedData, 'distance_traveled')) ?>;
const costPerKmData = costData.map((c, i) => distanceData[i] > 0 ? (c / distanceData[i]).toFixed(2) : 0);
const weightData = <?= json_encode(array_map(fn($r) => $r['cargo_weight'] ?? 0, $joinedData)) ?>;
const capacityData = <?= json_encode(array_map(fn($r) => $r['vehicle_capacity'] ?? 1, $joinedData)) ?>;
const loadUtilData = weightData.map((w, i) => capacityData[i] > 0 ? ((w / capacityData[i]) * 100).toFixed(1) : 0);

// Chart 1: Cost per Trip
new Chart(document.getElementById('costPerTripChart'), {
    type: 'bar',
    data: {
        labels: tripLabels,
        datasets: [{
            label: 'Total Cost',
            data: costData,
            backgroundColor: 'rgba(59, 130, 246, 0.7)' // Tailwind blue-500
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Chart 2: Cost Breakdown
new Chart(document.getElementById('costBreakdownChart'), {
    type: 'pie',
    data: {
        labels: ['Fuel', 'Toll', 'Other'],
        datasets: [{
            data: [
                fuelData.reduce((a,b)=>a+parseFloat(b),0),
                tollData.reduce((a,b)=>a+parseFloat(b),0),
                otherData.reduce((a,b)=>a+parseFloat(b),0)
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.7)',  // green-500
                'rgba(234, 179, 8, 0.7)',  // yellow-500
                'rgba(239, 68, 68, 0.7)'   // red-500
            ]
        }]
    },
    options: { responsive: true }
});

// Chart 3: Cost per km
new Chart(document.getElementById('costPerKmChart'), {
    type: 'line',
    data: {
        labels: tripLabels,
        datasets: [{
            label: 'Cost per km',
            data: costPerKmData,
            borderColor: 'rgba(99, 102, 241, 0.9)', // indigo-500
            backgroundColor: 'rgba(99, 102, 241, 0.4)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true }
});

// Chart 4: Load Utilization
new Chart(document.getElementById('loadUtilChart'), {
    type: 'bar',
    data: {
        labels: tripLabels,
        datasets: [{
            label: 'Utilization %',
            data: loadUtilData,
            backgroundColor: 'rgba(16, 185, 129, 0.7)' // emerald-500
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
</script>
