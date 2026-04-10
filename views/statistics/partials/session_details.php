<?php

$session = $data['session'] ?? [];
$steps = $data['steps'] ?? [];
$timeSeries = $data['time_series'] ?? [];
?>

<div class="session-details">
    <h4 class="mb-3">Сессия #<?= $session['id'] ?? '?' ?></h4>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Параметры сессии</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">Дата создания:</td>
                            <td class="text-end"><?= date('d.m.Y H:i:s', strtotime($session['start_time'] ?? 'now')) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Статус:</td>
                            <td class="text-end">
                                <?php if (($session['status'] ?? '') == 'active'): ?>
                                    <span class="badge bg-success">Активна</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Завершена</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Количество станков:</td>
                            <td class="text-end"><?= $session['machines_count'] ?? 0 ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Количество ремонтников:</td>
                            <td class="text-end"><?= $session['repairmen_count'] ?? 0 ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Параметр k (работа):</td>
                            <td class="text-end"><?= number_format($session['k_work'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Параметр k (ремонт):</td>
                            <td class="text-end"><?= number_format($session['k_repair'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Доход в час:</td>
                            <td class="text-end"><?= number_format($session['revenue_rate'] ?? 0, 2) ?> ₽</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Итоговые показатели</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">Всего шагов:</td>
                            <td class="text-end"><?= count($steps) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Финальная прибыль:</td>
                            <td class="text-end <?= (($steps[count($steps)-1]['total_profit'] ?? 0) >= 0) ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($steps[count($steps)-1]['total_profit'] ?? 0, 0, '.', ' ') ?> ₽
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Максимальная прибыль:</td>
                            <td class="text-end text-success">
                                <?= number_format(max(array_column($steps, 'total_profit') ?: [0]), 0, '.', ' ') ?> ₽
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Всего ремонтов:</td>
                            <td class="text-end"><?= number_format($steps[count($steps)-1]['total_repairs'] ?? 0, 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Общее время простоя:</td>
                            <td class="text-end"><?= number_format($steps[count($steps)-1]['total_downtime'] ?? 0, 1) ?> ч</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($timeSeries)): ?>
    <div class="mb-4">
        <canvas id="sessionProfitChart" height="300"></canvas>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Шаг</th>
                    <th>Время (ч)</th>
                    <th>Работает</th>
                    <th>Ремонт</th>
                    <th>Очередь</th>
                    <th>Прибыль</th>
                    <th>Прибыль за период</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeSeries as $step): ?>
                <tr>
                    <td><?= $step['step_number'] ?></td>
                    <td><?= $step['total_time'] ?></td>
                    <td><?= $step['working_machines'] ?></td>
                    <td><?= $step['repair_machines'] ?></td>
                    <td><?= $step['waiting_machines'] ?></td>
                    <td class="fw-bold"><?= number_format($step['total_profit'], 0, '.', ' ') ?> ₽</td>
                    <td class="<?= ($step['period_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($step['period_profit'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($step['period_profit'] ?? 0, 0, '.', ' ') ?> ₽
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
    if (document.getElementById('sessionProfitChart')) {
        const timeSeries = <?= json_encode($timeSeries) ?>;
        new Chart(document.getElementById('sessionProfitChart'), {
            type: 'line',
            data: {
                labels: timeSeries.map(s => s.step_number),
                datasets: [
                    {
                        label: 'Общая прибыль (₽)',
                        data: timeSeries.map(s => s.total_profit),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52,152,219,0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Прибыль за период (₽)',
                        data: timeSeries.map(s => s.period_profit || 0),
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39,174,96,0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString() + ' ₽';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: { display: true, text: 'Общая прибыль (₽)', color: '#3498db' }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: false,
                        title: { display: true, text: 'Прибыль за период (₽)', color: '#27ae60' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }
    </script>
    <?php endif; ?>
</div>