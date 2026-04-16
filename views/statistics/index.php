<?php
$dashboard = $data['dashboard'] ?? [];
$hourlyStats = $data['hourlyStats'] ?? [];
$efficiencyStats = $data['efficiencyStats'] ?? [];
$dailyStats = $data['dailyStats'] ?? [];
$weibullStats = $data['weibullStats'] ?? [];
$statusDistribution = $data['statusDistribution'] ?? [];
$repairStats = $data['repairStats'] ?? [];

$avgProfitPerStep = ($dashboard['steps_stats']['total_steps'] ?? 0) > 0 
    ? ($dashboard['steps_stats']['avg_period_profit'] ?? 0)
    : 0;
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                Панель управления статистикой
                <button class="btn btn-sm btn-outline-primary float-end" onclick="refreshData()">
                    <i class="fas fa-refresh me-1"></i>Обновить
                </button>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="display-6 text-primary mb-2"><i class="fas fa-industry"></i></div>
                <h3 class="mb-0"><?= number_format($dashboard['param_stats']['avg_machines'] ?? 0, 0) ?></h3>
                <p class="text-muted mb-0">Среднее кол-во станков</p>
                <small class="text-muted">от <?= $dashboard['param_stats']['min_machines'] ?? 0 ?> до <?= $dashboard['param_stats']['max_machines'] ?? 0 ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="display-6 text-warning mb-2"><i class="fas fa-wrench"></i></div>
                <h3 class="mb-0"><?= number_format($dashboard['param_stats']['avg_repairmen'] ?? 0, 1) ?></h3>
                <p class="text-muted mb-0">Среднее кол-во ремонтников</p>
                <small class="text-muted">от <?= $dashboard['param_stats']['min_repairmen'] ?? 0 ?> до <?= $dashboard['param_stats']['max_repairmen'] ?? 0 ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="display-6 text-success mb-2"><i class="fas fa-ruble-sign"></i></div>
                <h3 class="mb-0 <?= ($dashboard['profit_stats']['avg_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= number_format($dashboard['profit_stats']['avg_profit'] ?? 0, 0, '.', ' ') ?>
                </h3>
                <p class="text-muted mb-0">Средняя прибыль</p>
                <small class="text-success">▲ макс: <?= number_format($dashboard['profit_stats']['max_profit'] ?? 0, 0, '.', ' ') ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="display-6 text-info mb-2"><i class="fas fa-chart-simple"></i></div>
                <h3 class="mb-0"><?= number_format($avgProfitPerStep, 0, '.', ' ') ?></h3>
                <p class="text-muted mb-0">Прибыль за шаг</p>
                <small class="text-muted">в среднем за период</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row mb-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy me-2 text-warning"></i>
                Топ-10 сессий по прибыли
            </div>
            <div class="card-body">
                <canvas id="topProfitChart" height="300" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2 text-success"></i>
                Текущее состояние станков
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="250" style="max-height: 250px;"></canvas>
                <div class="row text-center mt-3">
                    <div class="col-4">
                        <span class="badge bg-success">Работают</span>
                        <div class="mt-1"><strong><?= $statusDistribution['working'] ?? 0 ?></strong></div>
                    </div>
                    <div class="col-4">
                        <span class="badge bg-warning">Ремонт</span>
                        <div class="mt-1"><strong><?= $statusDistribution['repair'] ?? 0 ?></strong></div>
                    </div>
                    <div class="col-4">
                        <span class="badge bg-danger">Ожидают</span>
                        <div class="mt-1"><strong><?= $statusDistribution['waiting'] ?? 0 ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock me-2 text-info"></i>
                Активность по часам суток
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" height="300" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                Эффективность (прибыль на станок)
            </div>
            <div class="card-body">
                <canvas id="efficiencyChart" height="300" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Additional Metrics Row -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-wrench me-2 text-warning"></i>
                Статистика ремонтов
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <p class="text-muted mb-1">Среднее время ремонта</p>
                    <h3 class="mb-0"><?= number_format($repairStats['avg_repair_time'] ?? 0, 1) ?> <small class="fs-6">ч</small></h3>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: <?= min(100, (($repairStats['avg_repair_time'] ?? 0) / 10) * 100) ?>%"></div>
                    </div>
                    <small class="text-muted">
                        мин: <?= number_format($repairStats['min_repair_time'] ?? 0, 1) ?> ч / 
                        макс: <?= number_format($repairStats['max_repair_time'] ?? 0, 1) ?> ч
                    </small>
                </div>
                <div>
                    <p class="text-muted mb-1">Всего ремонтов</p>
                    <h3 class="mb-0"><?= number_format($repairStats['total_repair_jobs'] ?? 0, 0) ?></h3>
                    <small class="text-muted">уникальных станков: <?= $repairStats['unique_machines_repaired'] ?? 0 ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-scatter me-2 text-info"></i>
                Параметры Вейбулла
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">k (форма работы)</span>
                        <strong><?= number_format($dashboard['param_stats']['avg_k_work'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: <?= min(100, (($dashboard['param_stats']['avg_k_work'] ?? 0) / 10) * 100) ?>%"></div>
                    </div>
                    <small class="text-muted">
                        от <?= number_format($dashboard['param_stats']['min_k_work'] ?? 0, 2) ?> 
                        до <?= number_format($dashboard['param_stats']['max_k_work'] ?? 0, 2) ?>
                    </small>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">k (форма ремонта)</span>
                        <strong><?= number_format($dashboard['param_stats']['avg_k_repair'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: <?= min(100, (($dashboard['param_stats']['avg_k_repair'] ?? 0) / 10) * 100) ?>%"></div>
                    </div>
                    <small class="text-muted">
                        от <?= number_format($dashboard['param_stats']['min_k_repair'] ?? 0, 2) ?> 
                        до <?= number_format($dashboard['param_stats']['max_k_repair'] ?? 0, 2) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-simple me-2 text-success"></i>
                Общая статистика
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <p class="text-muted mb-1">Всего шагов моделирования</p>
                    <h3 class="mb-0"><?= number_format($dashboard['steps_stats']['total_steps'] ?? 0, 0, '.', ' ') ?></h3>
                </div>
                <div class="mb-3">
                    <p class="text-muted mb-1">Всего ремонтов (всех сессий)</p>
                    <h3 class="mb-0"><?= number_format($dashboard['steps_stats']['total_repairs_all'] ?? 0, 0, '.', ' ') ?></h3>
                </div>
                <div>
                    <p class="text-muted mb-1">Среднее время простоя</p>
                    <h3 class="mb-0"><?= number_format($dashboard['steps_stats']['avg_downtime'] ?? 0, 1) ?> <small class="fs-6">ч/станок</small></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Stats Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-alt me-2 text-info"></i>
                Динамика за последние 7 дней
            </div>
            <div class="card-body">
                <canvas id="dailyChart" height="300" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Sessions Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-table me-2"></i>
                История сессий моделирования
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Дата и время</th>
                                <th>Станков</th>
                                <th>Ремонтников</th>
                                <th>k работы</th>
                                <th>k ремонта</th>
                                <th>Доход/час</th>
                                <th>Прибыль</th>
                                <th>Шагов</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dashboard['sessions'])): ?>
                                <?php foreach ($dashboard['sessions'] as $session): ?>
                                <tr>
                                    <td><strong>#<?= $session['id'] ?></strong></td>
                                    <td><?= date('d.m.Y H:i:s', strtotime($session['start_time'])) ?></td>
                                    <td><?= $session['machines_count'] ?></td>
                                    <td><?= $session['repairmen_count'] ?></td>
                                    <td><?= number_format($session['k_work'], 2) ?></td>
                                    <td><?= number_format($session['k_repair'], 2) ?></td>
                                    <td><?= number_format($session['revenue_rate'], 2) ?> ₽</td>
                                    <td class="<?= ($session['max_profit'] ?? 0) >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold' ?>">
                                        <?= number_format($session['max_profit'] ?? 0, 0, '.', ' ') ?> ₽
                                    </td>
                                    <td><?= $session['steps_count'] ?? 0 ?></td>
                                    <td>
                                        <?php if (($session['status'] ?? '') == 'active'): ?>
                                            <span class="badge bg-success"><i class="fas fa-play me-1"></i>Активна</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-check me-1"></i>Завершена</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewSession(<?= $session['id'] ?>)">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSession(<?= $session['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="fas fa-database me-2"></i>Нет данных о сессиях
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Session Details -->
<div class="modal fade" id="sessionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали сессии</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sessionModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка данных...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
function createChart(elementId, type, data, options) {
    const canvas = document.getElementById(elementId);
    if (!canvas) {
        console.error('Canvas element not found:', elementId);
        return null;
    }
    
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (window.charts && window.charts[elementId]) {
        window.charts[elementId].destroy();
    }
    
    try {
        const chart = new Chart(ctx, {
            type: type,
            data: data,
            options: options
        });
        
        if (!window.charts) window.charts = {};
        window.charts[elementId] = chart;
        
        return chart;
    } catch (error) {
        console.error('Error creating chart:', error);
        return null;
    }
}

function renderCharts() {
    const topSessions = <?= json_encode(array_slice($dashboard['top_sessions'] ?? [], 0, 10)) ?>;
    if (topSessions.length > 0 && document.getElementById('topProfitChart')) {
        createChart('topProfitChart', 'bar', {
            labels: topSessions.map(s => 'Сессия #' + s.id),
            datasets: [{
                label: 'Прибыль (₽)',
                data: topSessions.map(s => s.final_profit || 0),
                backgroundColor: topSessions.map(s => (s.final_profit || 0) > 0 ? '#27ae60' : '#e74c3c'),
                borderRadius: 6
            }]
        }, {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' ₽' } } }
        });
    }
    
    const statusData = <?= json_encode($statusDistribution) ?>;
    if (statusData && document.getElementById('statusChart')) {
        const total = (statusData.working || 0) + (statusData.repair || 0) + (statusData.waiting || 0);
        createChart('statusChart', 'doughnut', {
            labels: ['Работают', 'В ремонте', 'Ожидают'],
            datasets: [{
                data: [statusData.working || 0, statusData.repair || 0, statusData.waiting || 0],
                backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
                borderWidth: 0
            }]
        }, {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { 
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw} (${total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0}%)` } }
            },
            cutout: '60%'
        });
    }
    
    const hourlyData = <?= json_encode($hourlyStats) ?>;
    if (hourlyData && document.getElementById('hourlyChart')) {
        const hours = Array.from({length: 24}, (_, i) => i + ':00');
        const hourCounts = Array(24).fill(0);
        hourlyData.forEach(h => { if (h.hour >= 0 && h.hour < 24) hourCounts[h.hour] = h.session_count; });
        
        createChart('hourlyChart', 'line', {
            labels: hours,
            datasets: [{
                label: 'Количество сессий',
                data: hourCounts,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52,152,219,0.05)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#3498db',
                pointBorderColor: '#fff',
                pointRadius: 4
            }]
        }, {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true } }
        });
    }
    
    const efficiencyData = <?= json_encode(array_slice($efficiencyStats, 0, 10)) ?>;
    if (efficiencyData.length > 0 && document.getElementById('efficiencyChart')) {
        createChart('efficiencyChart', 'bar', {
            labels: efficiencyData.map(e => '#' + e.id),
            datasets: [{
                label: 'Прибыль на станок (₽)',
                data: efficiencyData.map(e => e.profit_per_machine || 0),
                backgroundColor: '#3498db',
                borderRadius: 6
            }]
        }, {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true } }
        });
    }
    
    const dailyData = <?= json_encode($dailyStats) ?>;
    if (dailyData.length > 0 && document.getElementById('dailyChart')) {
        createChart('dailyChart', 'line', {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'Количество сессий',
                    data: dailyData.map(d => d.session_count),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52,152,219,0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Средняя прибыль (тыс. ₽)',
                    data: dailyData.map(d => (d.avg_profit || 0) / 1000),
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39,174,96,0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        }, {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Количество сессий', color: '#3498db' } },
                y1: { position: 'right', beginAtZero: true, title: { display: true, text: 'Средняя прибыль (тыс. ₽)', color: '#27ae60' }, grid: { drawOnChartArea: false } }
            }
        });
    }
}

async function viewSession(sessionId) {
    const modalEl = document.getElementById('sessionModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalBody = document.getElementById('sessionModalBody');
    
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Загрузка данных...</p></div>';
    modal.show();
    
    try {
        const response = await fetch(`/statistics/session/${sessionId}`);
        const session = await response.json();
        
        const stepsResponse = await fetch(`/statistics/session/${sessionId}/steps`);
        const stepsData = await stepsResponse.json();
        
        const steps = stepsData.steps || [];
        const timeSeries = stepsData.time_series || [];
        const finalProfit = steps.length > 0 ? steps[steps.length - 1].total_profit : 0;
        
        modalBody.innerHTML = `
            <h5>Сессия #${session.id}</h5>
            <table class="table table-sm">
                <tr><th>Дата создания</th><td>${new Date(session.start_time).toLocaleString()}</td></tr>
                <tr><th>Станков</th><td>${session.machines_count}</td></tr>
                <tr><th>Ремонтников</th><td>${session.repairmen_count}</td></tr>
                <tr><th>k работы</th><td>${session.k_work}</td></tr>
                <tr><th>k ремонта</th><td>${session.k_repair}</td></tr>
                <tr><th>Доход/час</th><td>${session.revenue_rate} ₽</td></tr>
                <tr><th>Статус</th><td>${session.status === 'active' ? 'Активна' : 'Завершена'}</td></tr>
                <tr><th>Всего шагов</th><td>${steps.length}</td></tr>
                <tr><th>Финальная прибыль</th><td class="${finalProfit >= 0 ? 'text-success' : 'text-danger'} fw-bold">${finalProfit.toLocaleString()} ₽</td></tr>
            </table>
            <canvas id="sessionChart" height="200" style="max-height: 200px;"></canvas>
        `;
        
        if (timeSeries.length > 0) {
            setTimeout(() => {
                const ctx = document.getElementById('sessionChart')?.getContext('2d');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: timeSeries.map(s => s.step_number),
                            datasets: [{
                                label: 'Прибыль (₽)',
                                data: timeSeries.map(s => s.total_profit),
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52,152,219,0.1)',
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            scales: { y: { beginAtZero: false } }
                        }
                    });
                }
            }, 100);
        }
        
    } catch (error) {
        console.error('Error loading session:', error);
        modalBody.innerHTML = '<div class="alert alert-danger">Ошибка загрузки данных</div>';
    }
}

async function deleteSession(sessionId) {
    if (!confirm('Вы уверены, что хотите удалить эту сессию?')) return;
    
    try {
        const response = await fetch(`/statistics/session/${sessionId}`, { method: 'DELETE' });
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting session:', error);
        alert('Ошибка при удалении сессии');
    }
}

function refreshData() {
    location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        renderCharts();
    } else {
        console.warn('Chart.js not loaded yet, waiting...');
        const checkInterval = setInterval(function() {
            if (typeof Chart !== 'undefined') {
                clearInterval(checkInterval);
                renderCharts();
            }
        }, 100);
        setTimeout(function() {
            clearInterval(checkInterval);
            if (typeof Chart === 'undefined') {
                console.error('Chart.js failed to load');
                document.querySelectorAll('canvas').forEach(canvas => {
                    canvas.parentElement.innerHTML = '<div class="alert alert-warning">График временно недоступен</div>';
                });
            }
        }, 5000);
    }
});
</script>