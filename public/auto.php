<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 400); 

define('BASE_URL', 'http://localhost:8000/index.php');
define('STEPS_PER_SIMULATION', 300); 
define('DELAY_BETWEEN_REQUESTS', 500000); 

$paramSets = [
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 4,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 2,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 4000,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 160,
        'repairmen' => 4,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 755,
        'repairmen' => 5,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 400,
        'repairmen' => 7,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 1.5,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 3.0,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 5.0,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 1.0,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 2.5,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 4.0,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 15,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 25,
    ],
    [
        'machines' => 50,
        'repairmen' => 3,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 30,
    ],
    [
        'machines' => 80,
        'repairmen' => 6,
        'k' => 2.5,
        'k_repair' => 2.0,
        'r' => 229,
    ],
    [
        'machines' => 100,
        'repairmen' => 8,
        'k' => 3.0,
        'k_repair' => 2.2,
        'r' => 25,
    ],
    [
        'machines' => 330,
        'repairmen' => 2,
        'k' => 1.8,
        'k_repair' => 1.5,
        'r' => 18,
    ],
    [
        'machines' => 150,
        'repairmen' => 12,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 1,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
    [
        'machines' => 50,
        'repairmen' => 10,
        'k' => 2.2,
        'k_repair' => 1.8,
        'r' => 20,
    ],
];

function sendPostRequest($url, $data) {
    $ch = curl_init();
    
    $postData = http_build_query($data);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);



    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    
    return [
        'success' => $httpCode == 200,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}


function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    
    echo $logMessage;
    
    file_put_contents('../logs/automation_log.txt', $logMessage, FILE_APPEND);
}

function createResultsCSV($results) {
    $filename = 'simulation_results_' . date('Y-m-d_H-i-s') . '.csv';
    
    $fp = fopen($filename, 'w');
    
    fputcsv($fp, [
        'Дата',
        'Время',
        'Станков',
        'Ремонтников',
        'k_работы',
        'k_ремонта',
        'Доход',
        'Описание',
        'Статус',
        'HTTP код',
        'Ошибка'
    ]);
    
    foreach ($results as $result) {
        fputcsv($fp, [
            $result['date'],
            $result['time'],
            $result['params']['machines'],
            $result['params']['repairmen'],
            $result['params']['k'],
            $result['params']['k_repair'],
            $result['params']['r'],
            $result['params']['description'],
            $result['success'] ? 'УСПЕХ' : 'ОШИБКА',
            $result['http_code'],
            $result['error']
        ]);
    }
    
    fclose($fp);
    
    return $filename;
}


logMessage("=" . str_repeat("=", 80) . "=");
logMessage("ЗАПУСК АВТОМАТИЗИРОВАННОГО СБОРА ДАННЫХ");
logMessage("URL: " . BASE_URL);
logMessage("Шагов на симуляцию: " . STEPS_PER_SIMULATION);
logMessage("Всего сценариев: " . count($paramSets));
logMessage("=" . str_repeat("=", 80) . "=");

$results = [];
$successCount = 0;
$errorCount = 0;

foreach ($paramSets as $index => $params) {
    $scenarioNumber = $index + 1;
    $totalScenarios = count($paramSets);

    print_r($params);
    
    logMessage("");
    logMessage("[" . str_repeat("*", 50) . "]");
    logMessage("СЦЕНАРИЙ #$scenarioNumber из $totalScenarios");
    logMessage("Параметры: Станков={$params['machines']}, Ремонтников={$params['repairmen']}, k={$params['k']}, k_рем={$params['k_repair']}, r={$params['r']}");
    logMessage("[" . str_repeat("*", 50) . "]");
    
    logMessage("Сброс и генерация новых станков...");
    
    $postData = [
        'machines' => $params['machines'],
        'repairmen' => $params['repairmen'],
        'k' => $params['k'],
        'k_repair' => $params['k_repair'],
        'r' => $params['r'],
        'step_hours' => 1,
        'generate' => 1
    ];
    
    // $response = sendPostRequest(BASE_URL, $postData);
    
    // if (!$response['success']) {
    //     logMessage("ОШИБКА при генерации: HTTP {$response['http_code']} - {$response['error']}", 'ERROR');
    //     $errorCount++;
        
    //     $results[] = [
    //         'date' => date('Y-m-d'),
    //         'time' => date('H:i:s'),
    //         'params' => $params,
    //         'success' => false,
    //         'http_code' => $response['http_code'],
    //         'error' => $response['error']
    //     ];
        
    //     usleep(DELAY_BETWEEN_REQUESTS);
    //     continue;
    // }
    
    // logMessage("Генерация успешна");
    
    logMessage("Запуск симуляции на " . STEPS_PER_SIMULATION . " шагов...");
    
    $stepData = [
        'machines' => $params['machines'],
        'repairmen' => $params['repairmen'],
        'k' => $params['k'],
        'k_repair' => $params['k_repair'],
        'r' => $params['r'],
        'step_hours' => 1,
        'auto_step' => STEPS_PER_SIMULATION
    ];
    
    $response = sendPostRequest(BASE_URL, $stepData);

    if (!$response['success']) {
        logMessage("ОШИБКА при симуляции: HTTP {$response['http_code']} - {$response['error']}", 'ERROR');
        $errorCount++;
        
        $results[] = [
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'params' => $params,
            'success' => false,
            'http_code' => $response['http_code'],
            'error' => $response['error']
        ];
    } else {
        logMessage("Симуляция завершена успешно");
        $successCount++;
        
        $results[] = [
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'params' => $params,
            'success' => true,
            'http_code' => $response['http_code'],
            'error' => ''
        ];
    }
    
    usleep(DELAY_BETWEEN_REQUESTS);
}

logMessage("");
logMessage("=" . str_repeat("=", 80) . "=");
logMessage("Результаты");
logMessage("=" . str_repeat("=", 80) . "=");
logMessage("Всего сценариев: " . count($paramSets));
logMessage("Успешно: $successCount");
logMessage("С ошибками: $errorCount");

$csvFile = createResultsCSV($results);
logMessage("Результаты сохранены в файл: $csvFile");

logMessage("");
logMessage("Готово! Все данные сохранены в базе данных.");
logMessage("Для анализа выполните SQL запросы к таблицам:");
logMessage("- simulation_sessions");
logMessage("- simulation_steps");
logMessage("- machines_state");