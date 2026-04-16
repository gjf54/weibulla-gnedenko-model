<?php

namespace App\Facades;

use App\Helpers\EnvHelper;

class DB {
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private static ?\mysqli $conn = null;
    private bool $isConnected = false;
    
    public function __construct() {
        $this->host = EnvHelper::getString('DB_HOST', '127.0.0.1:3306');
        $this->username = EnvHelper::getString('DB_USERNAME', 'root');
        $this->password = EnvHelper::getString('DB_PASSWORD', '123123');
        $this->database = EnvHelper::getString('DB_DATABASE', 'schema');
        
        $this->connect();
    }

    private function connect() 
    {
        if ($this->isConnected && self::$conn !== null) {
            return;
        }
        
        self::$conn = new \mysqli($this->host, $this->username, $this->password, $this->database);
        if (self::$conn->connect_error) {
            $errorMsg = "Connection failed: " . self::$conn->connect_error;
            
            if (EnvHelper::getBool('APP_DEBUG', true)) {
                die($errorMsg);
            } else {
                error_log($errorMsg);
                die("Database connection error. Please try again later.");
            }
        }
        
        self::$conn->set_charset("utf8");
        
        $timezone = EnvHelper::getString('APP_TIMEZONE', 'Europe/Moscow');
        self::$conn->query("SET time_zone = '{$timezone}'");
        
        $this->isConnected = true;
    }
    
    private function ensureConnection(): void
    {
        if (!$this->isConnected || self::$conn === null) {
            $this->connect();
        }
    }

    public function getConnection() 
    {
        $this->ensureConnection();
        return self::$conn;
    }

    public function prepare($sql) 
    {
        $this->ensureConnection();
        return self::$conn->prepare($sql);
    }

    public function insertId() 
    {
        $this->ensureConnection();
        return self::$conn->insert_id;
    }

    public function escapeString($string) 
    {
        $this->ensureConnection();
        return self::$conn->real_escape_string($string);
    }

    public function beginTransaction() 
    {
        $this->ensureConnection();
        self::$conn->begin_transaction();
    }

    public function commit() 
    {
        $this->ensureConnection();
        self::$conn->commit();
    }

    public function rollback() 
    {
        $this->ensureConnection();
        self::$conn->rollback();
    }

    public function close() 
    {
        if (self::$conn !== null && $this->isConnected) {
            self::$conn->close();
            $this->isConnected = false;
            self::$conn = null;
        }
    }

    public function query($sql) 
    {
        $this->ensureConnection();
        return self::$conn->query($sql);
    }
    
    public function getAllSessions(): array 
    {
        $sessions = [];
        
        $query = "SELECT 
                    s.*,
                    COUNT(st.id) as steps_count,
                    MAX(st.total_profit) as max_profit,
                    MAX(st.total_time) as total_time
                  FROM simulation_sessions s
                  LEFT JOIN simulation_steps st ON s.id = st.session_id
                  GROUP BY s.id
                  ORDER BY s.start_time DESC";
        
        $result = $this->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sessions[] = $row;
            }
        }
        
        return $sessions;
    }

    public function getStepsStats(): array 
    {
        $query = "SELECT 
                COUNT(*) as total_steps,
                COALESCE(AVG(working_machines), 0) as avg_working,
                COALESCE(AVG(repair_machines), 0) as avg_repair,
                COALESCE(AVG(waiting_machines), 0) as avg_waiting,
                COALESCE(AVG(queue_length), 0) as avg_queue,
                COALESCE((
                    SELECT SUM(final_repairs)
                    FROM (
                        SELECT MAX(total_repairs) as final_repairs
                        FROM simulation_steps
                        GROUP BY session_id
                    ) as fr
                ), 0) as total_repairs_all,
                COALESCE((
                    SELECT AVG(final_downtime / machines_count)
                    FROM (
                        SELECT 
                            MAX(st.total_downtime) as final_downtime,
                            MAX(s.machines_count) as machines_count
                        FROM simulation_steps st
                        JOIN simulation_sessions s ON st.session_id = s.id
                        GROUP BY st.session_id
                    ) as fd
                ), 0) as avg_downtime,
                COALESCE(AVG(period_profit), 0) as avg_period_profit
            FROM simulation_steps";
        
        $result = $this->query($query);
        return $result ? $result->fetch_assoc() : [];
    }

    public function getParameterStats(): array 
    {
        $query = "SELECT 
                    COALESCE(AVG(machines_count), 0) as avg_machines,
                    COALESCE(MIN(machines_count), 0) as min_machines,
                    COALESCE(MAX(machines_count), 0) as max_machines,
                    COALESCE(AVG(repairmen_count), 0) as avg_repairmen,
                    COALESCE(MIN(repairmen_count), 0) as min_repairmen,
                    COALESCE(MAX(repairmen_count), 0) as max_repairmen,
                    COALESCE(AVG(k_work), 0) as avg_k_work,
                    COALESCE(MIN(k_work), 0) as min_k_work,
                    COALESCE(MAX(k_work), 0) as max_k_work,
                    COALESCE(AVG(k_repair), 0) as avg_k_repair,
                    COALESCE(MIN(k_repair), 0) as min_k_repair,
                    COALESCE(MAX(k_repair), 0) as max_k_repair,
                    COALESCE(AVG(revenue_rate), 0) as avg_revenue,
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_sessions,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
                  FROM simulation_sessions";
        
        $result = $this->query($query);
        return $result ? $result->fetch_assoc() : [];
    }

    public function getTopProfitSessions(int $limit = 10): array 
    {
        $sessions = [];
        
        $query = "SELECT 
                    s.id,
                    s.machines_count,
                    s.repairmen_count,
                    s.k_work,
                    s.k_repair,
                    s.revenue_rate,
                    s.start_time,
                    s.status,
                    COALESCE(MAX(st.total_profit), 0) as final_profit,
                    COUNT(st.id) as steps_count
                  FROM simulation_sessions s
                  LEFT JOIN simulation_steps st ON s.id = st.session_id
                  GROUP BY s.id
                  HAVING final_profit IS NOT NULL
                  ORDER BY final_profit DESC
                  LIMIT ?";
        
        $stmt = $this->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        $stmt->close();
        return $sessions;
    }

    public function getHourlyStats(): array 
    {
        $stats = [];
        
        $query = "SELECT 
                    HOUR(start_time) as hour,
                    COUNT(*) as session_count,
                    COALESCE(AVG((SELECT MAX(total_profit) FROM simulation_steps WHERE session_id = s.id)), 0) as avg_profit
                  FROM simulation_sessions s
                  GROUP BY HOUR(start_time)
                  ORDER BY hour";
        
        $result = $this->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        
        return $stats;
    }

    public function getDailyStats(int $days = 30): array 
    {
        $stats = [];
        
        $query = "SELECT 
                    DATE(start_time) as date,
                    COUNT(*) as session_count,
                    COALESCE(AVG(machines_count), 0) as avg_machines,
                    COALESCE(AVG(repairmen_count), 0) as avg_repairmen,
                    COALESCE(AVG((SELECT AVG(total_profit) FROM simulation_steps WHERE session_id = s.id)), 0) as avg_profit
                  FROM simulation_sessions s
                  WHERE start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(start_time)
                  ORDER BY date DESC";
        
        $stmt = $this->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        $stmt->close();
        return $stats;
    }

    public function getCorrelationData(): array 
    {
        $data = [];
        
        $query = "SELECT 
                    s.machines_count,
                    s.repairmen_count,
                    s.k_work,
                    s.k_repair,
                    s.revenue_rate,
                    COALESCE(AVG(st.total_profit), 0) as avg_profit,
                    COALESCE(AVG(st.total_repairs), 0) as avg_repairs,
                    COALESCE(AVG(st.total_downtime), 0) as avg_downtime,
                    COALESCE(AVG(st.working_machines), 0) as avg_working,
                    COUNT(DISTINCT st.id) as steps_count
                  FROM simulation_sessions s
                  JOIN simulation_steps st ON s.id = st.session_id
                  GROUP BY s.id
                  ORDER BY avg_profit DESC";
        
        $result = $this->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

   public function getStatusDistribution(): array
    {
        $stats = ['working' => 0, 'repair' => 0, 'waiting' => 0];

        $query = "SELECT ms.status, COUNT(*) as cnt
                FROM machines_state ms
                INNER JOIN (
                    SELECT session_id, MAX(step_number) as max_step
                    FROM simulation_steps
                    GROUP BY session_id
                ) last_step ON ms.step_id = (
                    SELECT id FROM simulation_steps 
                    WHERE session_id = last_step.session_id AND step_number = last_step.max_step 
                    LIMIT 1
                )
                GROUP BY ms.status";

        $result = $this->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats[$row['status']] = (int)$row['cnt'];
            }
        }
        return $stats;
    }

    public function getRepairStats(): array 
    {
        $query = "SELECT 
                    COALESCE(AVG(repair_time), 0) as avg_repair_time,
                    COALESCE(MIN(repair_time), 0) as min_repair_time,
                    COALESCE(MAX(repair_time), 0) as max_repair_time,
                    COUNT(*) as total_repair_jobs,
                    COALESCE(AVG(repair_remaining), 0) as avg_repair_remaining,
                    COUNT(DISTINCT machine_number) as unique_machines_repaired
                  FROM repair_queue_state
                  WHERE repair_remaining <= 1";
        
        $result = $this->query($query);
        return $result ? $result->fetch_assoc() : [];
    }

    public function getSessionDetails(int $sessionId): ?array 
    {
        $query = "SELECT * FROM simulation_sessions WHERE id = ?";
        $stmt = $this->prepare($query);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        return $session;
    }

    public function getSessionSteps(int $sessionId): array 
    {
        $steps = [];
        
        $query = "SELECT * FROM simulation_steps 
                  WHERE session_id = ? 
                  ORDER BY step_number";
        
        $stmt = $this->prepare($query);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $steps[] = $row;
        }
        
        $stmt->close();
        return $steps;
    }

    public function getFinalMachineState(int $sessionId): array 
    {
        $machines = [];
        
        $query = "SELECT m.* 
                  FROM machines_state m
                  JOIN simulation_steps s ON m.step_id = s.id
                  WHERE s.session_id = ? 
                  AND s.step_number = (SELECT MAX(step_number) FROM simulation_steps WHERE session_id = ?)
                  ORDER BY m.machine_number";
        
        $stmt = $this->prepare($query);
        $stmt->bind_param("ii", $sessionId, $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $machines[] = $row;
        }
        
        $stmt->close();
        return $machines;
    }

    public function getSessionHistory(int $sessionId): array 
    {
        $history = [];
        
        $query = "SELECT * FROM machine_history 
                  WHERE session_id = ? 
                  ORDER BY step_number, timestamp";
        
        $stmt = $this->prepare($query);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        $stmt->close();
        return $history;
    }

    public function getProfitStats(): array
    {
        $query = "SELECT 
                    COALESCE(AVG(final_profit), 0) as avg_profit,
                    COALESCE(MAX(final_profit), 0) as max_profit,
                    COALESCE(MIN(final_profit), 0) as min_profit
                  FROM (
                      SELECT MAX(total_profit) as final_profit
                      FROM simulation_steps
                      GROUP BY session_id
                  ) as session_profits";
        $result = $this->query($query);
        return $result ? $result->fetch_assoc() : [];
    }

    public function getWeibullStats(): array 
    {
        $query = "SELECT 
                    'work' as type,
                    COALESCE(AVG(k_work), 0) as avg_k,
                    COALESCE(MIN(k_work), 0) as min_k,
                    COALESCE(MAX(k_work), 0) as max_k,
                    COALESCE(AVG(theta_work), 0) as avg_theta
                  FROM simulation_sessions
                  UNION ALL
                  SELECT 
                    'repair' as type,
                    COALESCE(AVG(k_repair), 0) as avg_k,
                    COALESCE(MIN(k_repair), 0) as min_k,
                    COALESCE(MAX(k_repair), 0) as max_k,
                    COALESCE(AVG(theta_repair), 0) as avg_theta
                  FROM simulation_sessions";
        
        $result = $this->query($query);
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $stats[$row['type']] = $row;
        }
        
        return $stats;
    }

    public function getEfficiencyStats(): array 
    {
        $query = "SELECT 
                    s.id,
                    s.machines_count,
                    s.repairmen_count,
                    COALESCE(MAX(st.total_profit), 0) as total_profit,
                    COALESCE(MAX(st.total_profit) / s.machines_count, 0) as profit_per_machine,
                    COALESCE(MAX(st.total_repairs), 0) as total_repairs,
                    COALESCE(MAX(st.total_repairs) / s.machines_count, 0) as repairs_per_machine,
                    COALESCE(MAX(st.total_downtime) / s.machines_count, 0) as downtime_per_machine
                  FROM simulation_sessions s
                  LEFT JOIN simulation_steps st ON s.id = st.session_id
                  GROUP BY s.id
                  HAVING total_profit IS NOT NULL
                  ORDER BY profit_per_machine DESC
                  LIMIT 20";
        
        $result = $this->query($query);
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }

    public function getTimeSeriesData(int $sessionId): array 
    {
        $data = [];
        
        $query = "SELECT 
                    step_number,
                    total_time,
                    working_machines,
                    repair_machines,
                    waiting_machines,
                    total_profit,
                    period_profit
                  FROM simulation_steps
                  WHERE session_id = ?
                  ORDER BY step_number";
        
        $stmt = $this->prepare($query);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }

    public function deleteSessionById(int $sessionId): array 
    {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        try {
            $this->beginTransaction();
            
            $stmt = $this->prepare("DELETE FROM simulation_sessions WHERE id = ?");
            $stmt->bind_param("i", $sessionId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $result['success'] = true;
                $result['message'] = "Сессия #$sessionId успешно удалена";
            } else {
                $result['message'] = "Сессия #$sessionId не найдена";
            }
            
            $stmt->close();
            $this->commit();
            
        } catch (\Exception $e) {
            $this->rollback();
            $result['message'] = "Ошибка: " . $e->getMessage();
        }
        
        return $result;
    }

    public function deleteOldSessions(int $days = 30): array 
    {
        $result = [
            'success' => false,
            'message' => '',
            'deleted_count' => 0
        ];
        
        try {
            $this->beginTransaction();
            
            $stmt = $this->prepare("DELETE FROM simulation_sessions WHERE start_time < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            
            $result['deleted_count'] = $stmt->affected_rows;
            $result['success'] = true;
            $result['message'] = "Удалено сессий старше $days дней: " . $result['deleted_count'];
            
            $stmt->close();
            $this->commit();
            
        } catch (\Exception $e) {
            $this->rollback();
            $result['message'] = "Ошибка: " . $e->getMessage();
        }
        
        return $result;
    }

    public function getDashboardSummary(): array
    {
        return [
            'sessions' => $this->getAllSessions(),
            'steps_stats' => $this->getStepsStats(),
            'param_stats' => $this->getParameterStats(),
            'profit_stats' => $this->getProfitStats(),
            'top_sessions' => $this->getTopProfitSessions(5),
            'status_distribution' => $this->getStatusDistribution(),
            'repair_stats' => $this->getRepairStats(),
            'hourly_stats' => $this->getHourlyStats(),
            'efficiency' => $this->getEfficiencyStats(),
        ];
    }

    public function deleteAllSessions($keepLastSession = false): array 
    {
        $result = [
            'success' => false,
            'message' => '',
            'deleted_count' => 0
        ];

        try {
            $this->beginTransaction();

            if ($keepLastSession) {
                $lastSessionQuery = "SELECT id FROM simulation_sessions ORDER BY id DESC LIMIT 1";
                $lastSessionResult = $this->query($lastSessionQuery);

                if ($lastSessionResult && $lastSessionResult->num_rows > 0) {
                    $lastSessionRow = $lastSessionResult->fetch_assoc();
                    $lastSessionId = $lastSessionRow['id'];

                    $deleteQuery = "DELETE FROM simulation_sessions WHERE id != $lastSessionId";
                    $this->query($deleteQuery);

                    $result['deleted_count'] = self::$conn->affected_rows;
                    $result['message'] = "Удалены все сессии, кроме последней (ID: $lastSessionId). Удалено сессий: " . $result['deleted_count'];
                } else {
                    $result['message'] = "Нет сессий для удаления";
                }
            } else {
                $deleteQuery = "DELETE FROM simulation_sessions";
                $this->query($deleteQuery);

                $result['deleted_count'] = self::$conn->affected_rows;
                $result['message'] = "Удалены все сессии. Удалено сессий: " . $result['deleted_count'];
            }

            $this->commit();
            $result['success'] = true;

        } catch (\Exception $e) {
            $this->rollback();
            $result['message'] = "Ошибка при удалении сессий: " . $e->getMessage();
        }

        return $result;
    }
}