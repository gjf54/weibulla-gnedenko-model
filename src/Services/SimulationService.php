<?php

namespace App\Services;

use App\Entity\MachinesStatus;
use App\Models\Machine;
use App\Models\RepairJob;
use App\Helpers\WeibullCalculatorHelper;
use App\Helpers\ProfitCalculator;
use App\Helpers\ProfitCalculatorHelper;
use App\Helpers\RandomGeneratorHelper;

class SimulationService
{
    private array $machines = [];
    private array $repairQueue = [];
    private int $stepCounter = 0;
    private float $totalProfit = 0;
    private float $lastSavedProfit = 0;
    private int $repairmen;
    private float $revenueRate;
    private float $k_work;
    private float $k_repair;
    private float $theta_work;
    private float $theta_repair;
    private int $stepHours;
    private int $machinesCount;
    private ?StepProcessorService $stepProcessor = null;
    private ?ProfitCalculatorHelper $profitCalculator = null;
    private ?DatabaseService $dbService = null;
    private ?int $dbSessionId = null;
    private array $workingCache = [];
    private array $repairCache = [];
    private array $waitingCache = [];
    
    public function __construct(
        int $machinesCount,
        int $repairmen,
        float $k_work,
        float $k_repair,
        float $theta_work,
        float $theta_repair,
        int $stepHours,
        float $revenueRate
    ) {
        $this->machinesCount = $machinesCount;
        $this->repairmen = $repairmen;
        $this->revenueRate = $revenueRate;
        $this->k_work = $k_work;
        $this->k_repair = $k_repair;
        $this->theta_work = $theta_work;
        $this->theta_repair = $theta_repair;
        $this->stepHours = $stepHours;
        
        $this->stepProcessor = new StepProcessorService($k_repair, $theta_repair, $repairmen);
        $this->profitCalculator = new ProfitCalculatorHelper($revenueRate, $machinesCount);
        
        $this->initializeMachines();
    }
    
    private function initializeMachines(): void
    {
        for ($i = 1; $i <= $this->machinesCount; $i++) {
            $workTime = WeibullCalculatorHelper::generateWorkTime($this->k_work, $this->theta_work);
            $this->machines[$i] = new Machine($i, RandomGeneratorHelper::getU(), $workTime);
        }
        $this->updateStatusCaches();
    }
    
    private function updateStatusCaches(): void
    {
        $this->workingCache = [];
        $this->repairCache = [];
        $this->waitingCache = [];
        
        foreach ($this->machines as $num => $machine) {
            if ($machine->status === MachinesStatus::WORKING->value) {
                $this->workingCache[] = $num;
            } elseif ($machine->status === MachinesStatus::REPAIR->value) {
                $this->repairCache[] = $num;
            } else {
                $this->waitingCache[] = $num;
            }
        }
    }
    
    public function setDatabaseService(DatabaseService $dbService, ?int $sessionId): void
    {
        $this->dbService = $dbService;
        $this->dbSessionId = $sessionId;
    }
    
    public function makeStep(): void
    {
        $this->stepCounter++;
        
        $workingBefore = count($this->workingCache);
        
        [$this->machines, $this->repairQueue, $repairedCount, $workingCount] = $this->stepProcessor->processStep(
            $this->machines,
            $this->repairQueue,
            $this->stepCounter,
            $this->stepHours,
            $this->getHistoryCallback()
        );
        
        $this->updateStatusCaches();
        
        $stepProfit = $this->profitCalculator->calculateStepProfit($workingCount, $this->repairmen);
        $this->totalProfit += $stepProfit;
        
        $this->saveStepToDatabase($workingCount, $repairedCount);
        $this->checkSimulationComplete();
    }
    
    public function makeSteps(int $count): void
    {
        $stepsToDo = min($count, 10000);
        
        for ($i = 0; $i < $stepsToDo; $i++) {
            if ($this->isSimulationComplete()) {
                break;
            }
            $this->makeStep();
        }
    }
    
    private function checkSimulationComplete(): void
    {
        if (empty($this->workingCache) && empty($this->repairQueue)) {
            if ($this->dbService && $this->dbSessionId) {
                $this->dbService->completeSession($this->dbSessionId);
            }
        }
    }
    
    public function isSimulationComplete(): bool
    {
        return empty($this->workingCache) && empty($this->repairQueue);
    }
    
    private function getHistoryCallback(): ?callable
    {
        if (!$this->dbService || !$this->dbSessionId) {
            return null;
        }
        
        return function($machineNum, $stepCounter, $eventType, $description) {
            $this->dbService->saveMachineHistory($this->dbSessionId, $stepCounter, $machineNum, $eventType, $description);
        };
    }
    
    private function saveStepToDatabase(int $workingCount, int $repairedCount): void
    {
        if (!$this->dbService || !$this->dbSessionId) {
            return;
        }
        
        $working = count($this->workingCache);
        $repair = count($this->repairCache);
        $waiting = count($this->waitingCache);
        $queueLength = count($this->repairQueue);
        
        $totalRepairs = 0;
        $totalDowntime = 0;
        $totalRemaining = 0;
        
        foreach ($this->machines as $machine) {
            $totalRepairs += $machine->repair_count;
            $totalDowntime += $machine->total_downtime;
            $totalRemaining += $machine->remaining;
        }
        
        $avgRemaining = $totalRemaining / $this->machinesCount;
        $periodProfit = $this->totalProfit - $this->lastSavedProfit;
        $this->lastSavedProfit = $this->totalProfit;
        
        try {
            $this->dbService->saveStep(
                $this->dbSessionId, $this->stepCounter, $this->stepHours,
                $working, $repair, $waiting, $queueLength, $totalRepairs,
                $totalDowntime, $this->totalProfit, $periodProfit, $avgRemaining,
                $this->machines, $this->repairQueue
            );
        } catch (\Exception $e) {
            error_log("DB error: " . $e->getMessage());
        }
    }
    
    public function reset(): void
    {
        if ($this->dbService && $this->dbSessionId) {
            $this->dbService->completeSession($this->dbSessionId);
        }
        
        $this->machines = [];
        $this->repairQueue = [];
        $this->stepCounter = 0;
        $this->totalProfit = 0;
        $this->lastSavedProfit = 0;
        $this->workingCache = [];
        $this->repairCache = [];
        $this->waitingCache = [];
        $this->initializeMachines();
    }
    
    public function getMachines(): array { return $this->machines; }
    public function getRepairQueue(): array { return $this->repairQueue; }
    public function getStepCounter(): int { return $this->stepCounter; }
    public function getTotalProfit(): float { return $this->totalProfit; }
    public function getMachinesCount(): int { return $this->machinesCount; }
    public function getRepairmen(): int { return $this->repairmen; }
    public function getStepHours(): int { return $this->stepHours; }
}