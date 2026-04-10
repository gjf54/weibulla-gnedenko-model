<?php
$simulation = $data['simulation'] ?? null;
$results = $data['results'] ?? [];
$repair_queue = $data['repair_queue'] ?? [];
$total_profit = $data['total_profit'] ?? 0;
$working_count = $data['working_count'] ?? 0;
$repair_count = $data['repair_count'] ?? 0;
$waiting_count = $data['waiting_count'] ?? 0;
$queue_length = $data['queue_length'] ?? 0;
$total_repairs = $data['total_repairs'] ?? 0;
$total_downtime = $data['total_downtime'] ?? 0;
$avg_remaining = $data['avg_remaining'] ?? 0;
$session_id = $data['session_id'] ?? null;
$last_params = $data['last_params'] ?? [];
$theta_work = $data['theta_work'] ?? 0;
$theta_repair = $data['theta_repair'] ?? 0;
?>

<style>
.step-progress {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    z-index: 1000;
    text-align: center;
    min-width: 300px;
}
.step-progress.active {
    display: block;
}
.step-progress .progress {
    height: 20px;
    margin: 15px 0;
}
.step-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}
.step-overlay.active {
    display: block;
}
.n-step-form {
    display: inline-flex;
    gap: 5px;
    align-items: center;
}
.n-step-input {
    width: 80px;
    text-align: center;
}
.btn-n-step {
    background: #6c5ce7;
    color: white;
}
.btn-n-step:hover {
    background: #5b4bc4;
    color: white;
}
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-microchip me-2"></i>
                Имитационная модель: работа и ремонт станков
            </div>
            <div class="card-body">
                <form method="POST" action="/simulation/generate" class="row g-3" id="generateForm">
                    <div class="col-md-2">
                        <label class="form-label">Станков:</label>
                        <input type="number" name="machines" class="form-control" value="<?= $last_params['machines'] ?? 50 ?>" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ремонтников:</label>
                        <input type="number" name="repairmen" class="form-control" value="<?= $last_params['repairmen'] ?? 3 ?>" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">k работы:</label>
                        <input type="number" name="k" class="form-control" value="<?= $last_params['k'] ?? 2.2 ?>" step="0.1" min="0.1" max="10">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">k ремонта:</label>
                        <input type="number" name="k_repair" class="form-control" value="<?= $last_params['k_repair'] ?? 1.8 ?>" step="0.1" min="0.1" max="10">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Доход/час:</label>
                        <input type="number" name="r" class="form-control" value="<?= $last_params['r'] ?? 20 ?>" step="0.1" min="0.1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Шаг (часы):</label>
                        <select name="step_hours" class="form-select">
                            <option value="1" <?= ($last_params['step_hours'] ?? 1) == 1 ? 'selected' : '' ?>>1 час</option>
                            <option value="8" <?= ($last_params['step_hours'] ?? 1) == 8 ? 'selected' : '' ?>>8 часов</option>
                        </select>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" name="generate" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i>Новая генерация
                        </button>
                    </div>
                </form>
                
                <?php if ($theta_work > 0): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <small>
                        <strong>Параметры распределения:</strong> 
                        Станки: k = <?= number_format($last_params['k'] ?? 0, 2) ?>, θ = <?= number_format($theta_work, 2) ?> | 
                        Ремонт: k = <?= number_format($last_params['k_repair'] ?? 0, 2) ?>, θ = <?= number_format($theta_repair, 2) ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($simulation && $simulation->getStepCounter() >= 0): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="fas fa-chart-simple me-2"></i>Управление симуляцией</span>
                    <div>
                        <strong>Шаг: <?= $simulation->getStepCounter() ?></strong> |
                        <strong>Прибыль: <?= number_format($total_profit, 2) ?> ₽</strong> |
                        <strong>Время: <?= $simulation->getStepCounter() * $simulation->getStepHours() ?> ч</strong>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <!-- Одиночный шаг -->
                    <form method="POST" action="/simulation/step" class="d-inline">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-step-forward me-1"></i>Шаг +<?= $simulation->getStepHours() ?> ч
                        </button>
                    </form>
                    
                    <!-- Предустановленные кнопки -->
                    <form method="POST" action="/simulation/auto-step" class="d-inline">
                        <input type="hidden" name="count" value="5">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-forward me-1"></i>5 шагов (<?= 5 * $simulation->getStepHours() ?> ч)
                        </button>
                    </form>
                    
                    <form method="POST" action="/simulation/auto-step" class="d-inline">
                        <input type="hidden" name="count" value="10">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-fast-forward me-1"></i>10 шагов (<?= 10 * $simulation->getStepHours() ?> ч)
                        </button>
                    </form>
                    
                    <form method="POST" action="/simulation/auto-step" class="d-inline">
                        <input type="hidden" name="count" value="24">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-forward-step me-1"></i>24 шага (<?= 24 * $simulation->getStepHours() ?> ч)
                        </button>
                    </form>
                    
                    <!-- Произвольное количество шагов -->
                    <form method="POST" action="/simulation/auto-step" class="n-step-form d-inline-flex" id="nStepForm">
                        <input type="number" name="count" class="form-control n-step-input" value="50" min="1" max="10000" id="nStepCount">
                        <button type="submit" class="btn btn-n-step" id="nStepBtn">
                            <i class="fas fa-play me-1"></i>N шагов
                        </button>
                    </form>
                    
                    <!-- Сброс -->
                    <form method="POST" action="/simulation/reset" class="d-inline">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo-alt me-1"></i>Сброс
                        </button>
                    </form>
                    
                    <!-- Очистка данных -->
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                        <i class="fas fa-trash-alt me-1"></i>Очистить данные
                    </button>
                    
                    <?php if ($session_id): ?>
                    <span class="text-muted small ms-auto">
                        <i class="fas fa-database me-1"></i>Сессия #<?= $session_id ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Прогресс-бар для длительных операций -->
<div class="step-overlay" id="stepOverlay"></div>
<div class="step-progress" id="stepProgress">
    <i class="fas fa-cog fa-spin fa-2x mb-2"></i>
    <h5>Выполнение шагов...</h5>
    <div class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated" id="stepProgressBar" style="width: 0%"></div>
    </div>
    <p id="stepProgressText">Подготовка...</p>
    <small class="text-muted">Пожалуйста, подождите</small>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Статистика
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-2">
                        <div class="alert alert-success mb-0">
                            <h5 class="mb-0"><?= $working_count ?></h5>
                            <small>Работает</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="alert alert-warning mb-0">
                            <h5 class="mb-0"><?= $repair_count ?></h5>
                            <small>В ремонте</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="alert alert-danger mb-0">
                            <h5 class="mb-0"><?= $waiting_count ?></h5>
                            <small>Очередь (всего: <?= $queue_length ?>)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="alert alert-info mb-0">
                            <h5 class="mb-0"><?= $total_repairs ?></h5>
                            <small>Ремонтов</small>
                        </div>
                    </div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-md-4">
                        <div class="text-muted">Средний ресурс</div>
                        <strong><?= number_format($avg_remaining, 1) ?> ч</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Средний простой</div>
                        <strong><?= number_format($total_downtime / max(1, count($results)), 1) ?> ч/станок</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Среднее время работы</div>
                        <strong><?= number_format($simulation->getStepCounter() * $simulation->getStepHours(), 1) ?> ч</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-table me-2"></i>Состояние станков
                <button class="btn btn-sm btn-outline-primary float-end" onclick="toggleMachineDetails()">
                    <i class="fas fa-eye me-1"></i>Показать все
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="machinesTable">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>U работы</th>
                                <th>Ресурс</th>
                                <th>Остаток</th>
                                <th>Статус</th>
                                <th>Ремонтов</th>
                                <th>Простой</th>
                                <th>Прогресс</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $machine): 
                                $progress = $machine->total_lifetime > 0 
                                    ? min(100, max(0, (($machine->total_lifetime - $machine->remaining) / $machine->total_lifetime * 100))) 
                                    : 100;
                            ?>
                            <tr class="machine-row">
                                <td><strong><?= $machine->num ?></strong></td>
                                <td><?= number_format($machine->u_work, 4) ?></td>
                                <td><?= number_format($machine->total_lifetime, 1) ?> ч</td>
                                <td><strong><?= number_format($machine->remaining, 1) ?> ч</strong></td>
                                <td>
                                    <?php if ($machine->status === 'working'): ?>
                                        <span class="badge bg-success">Работает</span>
                                    <?php elseif ($machine->status === 'repair'): ?>
                                        <span class="badge bg-warning">Ремонт</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ожидает</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $machine->repair_count ?></td>
                                <td><?= number_format($machine->total_downtime, 1) ?> ч</td>
                                <td style="width: 120px;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: <?= $progress ?>%; background: <?= $machine->status === 'working' ? '#198754' : ($machine->status === 'repair' ? '#ffc107' : '#dc3545') ?>;"></div>
                                    </div>
                                    <small class="text-muted"><?= round($progress) ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($repair_queue)): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock me-2"></i>Очередь ремонта (<?= count($repair_queue) ?> станков)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>№ станка</th>
                                <th>Время ремонта</th>
                                <th>Осталось</th>
                                <th>Поставлен</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repair_queue as $job): ?>
                            <tr>
                                <td><strong><?= $job->machine_num ?></strong></td>
                                <td><?= number_format($job->repair_time, 1) ?> ч</td>
                                <td><strong><?= number_format($job->repair_remaining, 1) ?> ч</strong></td>
                                <td>шаг <?= $job->added_step ?></td>
                                <td><?= $results[$job->machine_num]->status === 'repair' ? 'Ремонтируется' : 'Ожидает' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($working_count == 0 && empty($repair_queue)): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Все станки отказали и отремонтированы!</strong> Моделирование завершено.
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Modal для очистки данных -->
<div class="modal fade" id="clearDataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Очистка данных</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите очистить данные?</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="keepLastSession">
                    <label class="form-check-label" for="keepLastSession">
                        Сохранить последнюю сессию
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmClearData">Очистить</button>
            </div>
        </div>
    </div>
</div>

<script>
let showAllMachines = false;

function showProgress(totalSteps, currentStep = 0) {
    const overlay = document.getElementById('stepOverlay');
    const progressDiv = document.getElementById('stepProgress');
    const progressBar = document.getElementById('stepProgressBar');
    const progressText = document.getElementById('stepProgressText');
    
    overlay.classList.add('active');
    progressDiv.classList.add('active');
    
    if (totalSteps) {
        const percent = (currentStep / totalSteps) * 100;
        progressBar.style.width = percent + '%';
        progressText.textContent = `Выполнено ${currentStep} из ${totalSteps} шагов (${Math.round(percent)}%)`;
    } else {
        progressBar.style.width = '100%';
        progressText.textContent = 'Выполнение шагов...';
    }
}

function hideProgress() {
    const overlay = document.getElementById('stepOverlay');
    const progressDiv = document.getElementById('stepProgress');
    
    overlay.classList.remove('active');
    progressDiv.classList.remove('active');
}

async function runMultipleSteps(count) {
    showProgress(count, 0);
    
    try {
        const response = await fetch('/simulation/auto-step', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'count=' + count
        });
        
        const result = await response.json();
        
        if (result.success) {
            showProgress(count, count);
            setTimeout(() => {
                hideProgress();
                location.reload();
            }, 500);
        } else {
            hideProgress();
            alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
        }
        
    } catch (error) {
        console.error('Step error:', error);
        hideProgress();
        alert('Ошибка при выполнении шагов: ' + error.message);
    }
}

document.getElementById('nStepForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const count = parseInt(document.getElementById('nStepCount').value);
    
    if (isNaN(count) || count < 1) {
        alert('Введите корректное количество шагов (от 1 до 10000)');
        return;
    }
    
    if (count > 1000) {
        if (!confirm(`Вы собираетесь выполнить ${count} шагов. Это может занять некоторое время. Продолжить?`)) {
            return;
        }
    }
    
    runMultipleSteps(count);
});

function toggleMachineDetails() {
    const rows = document.querySelectorAll('.machine-row');
    showAllMachines = !showAllMachines;
    
    rows.forEach((row, index) => {
        if (!showAllMachines && index >= 20) {
            row.style.display = 'none';
        } else {
            row.style.display = '';
        }
    });
    
    const btn = document.querySelector('[onclick="toggleMachineDetails()"]');
    if (btn) {
        btn.innerHTML = showAllMachines ? 
            '<i class="fas fa-eye-slash me-1"></i>Скрыть' : 
            '<i class="fas fa-eye me-1"></i>Показать все';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.machine-row');
    rows.forEach((row, index) => {
        if (index >= 20) {
            row.style.display = 'none';
        }
    });
});

document.getElementById('confirmClearData')?.addEventListener('click', async function() {
    const keepLast = document.getElementById('keepLastSession').checked;
    
    try {
        const response = await fetch('/simulation/clear-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'keep_last=' + (keepLast ? '1' : '0')
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Ошибка: ' + result.message);
        }
    } catch (error) {
        console.error('Clear data error:', error);
        alert('Ошибка при очистке данных');
    }
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('clearDataModal'));
    modal.hide();
});

let autoRefreshInterval = null;

function toggleAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    } else {
        autoRefreshInterval = setInterval(() => {
            fetch('/simulation/state')
                .then(response => response.json())
                .then(data => {
                    if (data.step) {
                        const stepElements = document.querySelectorAll('.card-header .fw-bold');
                        if (stepElements[0]) stepElements[0].innerHTML = `<strong>Шаг: ${data.step}</strong>`;
                        if (stepElements[1]) stepElements[1].innerHTML = `<strong>Прибыль: ${data.profit.toLocaleString()} ₽</strong>`;
                    }
                })
                .catch(console.error);
        }, 30000);
    }
}

const controlsDiv = document.querySelector('.card-body .d-flex');
if (controlsDiv) {
    const autoRefreshBtn = document.createElement('button');
    autoRefreshBtn.type = 'button';
    autoRefreshBtn.className = 'btn btn-outline-secondary ms-auto';
    autoRefreshBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Автообновление выкл';
    autoRefreshBtn.onclick = function() {
        toggleAutoRefresh();
        this.innerHTML = autoRefreshInterval ? 
            '<i class="fas fa-sync-alt me-1"></i>Автообновление вкл' : 
            '<i class="fas fa-sync-alt me-1"></i>Автообновление выкл';
    };
    controlsDiv.appendChild(autoRefreshBtn);
}
</script>