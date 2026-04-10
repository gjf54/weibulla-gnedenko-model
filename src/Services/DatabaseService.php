<?php

namespace App\Services;

use App\Facades\DB;
use App\Models\Machine;
use App\Models\RepairJob;

class DatabaseService {
    private DB $db;
    
    public function __construct(DB $db) {
        $this->db = $db;
    }
    
    public function createSimulationSession(
        int $machines,
        int $repairmen,
        float $k,
        float $k_repair,
        float $theta_work,
        float $theta_repair,
        int $step_hours,
        float $revenue_rate
    ): int {
        $session_token = md5(uniqid() . time() . rand());
        
        $stmt = $this->db->prepare(
            "INSERT INTO simulation_sessions 
            (session_token, machines_count, repairmen_count, k_work, k_repair, 
             theta_work, theta_repair, step_hours, revenue_rate, status, start_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
        );
        
        $stmt->bind_param("siiidddii", $session_token, $machines, $repairmen, $k, $k_repair, $theta_work, $theta_repair, $step_hours, $revenue_rate);
        
        if (!$stmt->execute()) {
            throw new \Exception("Failed to create session: " . $stmt->error);
        }
        
        $session_id = $this->db->insertId();
        $stmt->close();
        
        return $session_id;
    }
    
    public function sessionExists(int $session_id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM simulation_sessions WHERE id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    public function getSessionStatus(int $session_id): ?string
    {
        $stmt = $this->db->prepare("SELECT status FROM simulation_sessions WHERE id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['status'] : null;
    }
    
    public function saveStep(
        int $session_id,
        int $step_number,
        int $step_hours,
        int $working_count,
        int $repair_count,
        int $waiting_count,
        int $queue_length,
        int $total_repairs,
        float $total_downtime,
        float $total_profit,
        float $period_profit,
        float $avg_remaining,
        array $machines_state,
        array $repair_queue
    ): int {
        if (!$this->sessionExists($session_id)) {
            throw new \Exception("Session {$session_id} does not exist");
        }
        
        $total_time = $step_number * $step_hours;
        
        $stmt = $this->db->prepare(
            "INSERT INTO simulation_steps 
            (session_id, step_number, total_time, working_machines, repair_machines, 
             waiting_machines, queue_length, total_repairs, total_downtime, 
             total_profit, period_profit, avg_remaining) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param("iiiididddddd", $session_id, $step_number, $total_time, $working_count, $repair_count, $waiting_count, $queue_length, $total_repairs, $total_downtime, $total_profit, $period_profit, $avg_remaining);
        
        if (!$stmt->execute()) {
            throw new \Exception("Failed to save step: " . $stmt->error);
        }
        
        $step_id = $this->db->insertId();
        $stmt->close();
        
        $this->saveMachinesState($step_id, $machines_state);
        
        $this->saveRepairQueueState($step_id, $repair_queue, $machines_state);
        
        return $step_id;
    }
    
    private function saveMachinesState(int $step_id, array $machines_state): void {
        $stmt = $this->db->prepare(
            "INSERT INTO machines_state 
            (step_id, machine_number, u_work, total_lifetime, remaining, status, 
             repair_count, total_downtime, repair_remaining, repair_time, failure_step) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($machines_state as $machine) {
            $repair_remaining = $machine->repair_remaining ?? 0;
            $repair_time = $machine->repair_time ?? 0;
            $failure_step = $machine->failure_step ?? null;
            
            $stmt->bind_param(
                "iidddsiddii", 
                $step_id, $machine->num, $machine->u_work, $machine->total_lifetime, 
                $machine->remaining, $machine->status, $machine->repair_count, 
                $machine->total_downtime, $repair_remaining, $repair_time, $failure_step
            );
            
            if (!$stmt->execute()) {
                throw new \Exception("Failed to save machine state: " . $stmt->error);
            }
        }
        $stmt->close();
    }
    
    private function saveRepairQueueState(int $step_id, array $repair_queue, array $machines_state): void {
        if (empty($repair_queue)) {
            return;
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO repair_queue_state 
            (step_id, machine_number, repair_time, repair_remaining, added_step, status, queue_position) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $position = 1;
        foreach ($repair_queue as $job) {
            $status = $machines_state[$job->machine_num]->status === 'repair' ? 'repairing' : 'waiting';
            
            $stmt->bind_param(
                "iidddis", 
                $step_id, $job->machine_num, $job->repair_time, 
                $job->repair_remaining, $job->added_step, $status, $position
            );
            
            if (!$stmt->execute()) {
                throw new \Exception("Failed to save repair queue state: " . $stmt->error);
            }
            $position++;
        }
        $stmt->close();
    }
    
    public function saveMachineHistory(int $session_id, int $step_number, int $machine_num, string $event_type, string $description): void {
        if (!$this->sessionExists($session_id)) {
            return;
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO machine_history (machine_number, session_id, step_number, event_type, description, timestamp) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("iiiss", $machine_num, $session_id, $step_number, $event_type, $description);
        
        if (!$stmt->execute()) {
            error_log("Failed to save machine history: " . $stmt->error);
        }
        $stmt->close();
    }
    
    public function updateSessionStatus(int $session_id, string $status): void {
        if (!$this->sessionExists($session_id)) {
            return;
        }
        
        $stmt = $this->db->prepare("UPDATE simulation_sessions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $session_id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function completeSession(int $session_id): void {
        $this->updateSessionStatus($session_id, 'completed');
    }
    
    public function activateSession(int $session_id): void {
        $this->updateSessionStatus($session_id, 'active');
    }
}