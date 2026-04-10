<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Facades\DatabaseManager;
use App\Services\DatabaseService;
use App\Services\SimulationService;
use App\Helpers\WeibullCalculatorHelper;
use App\Helpers\EnvHelper;

class SimulationController extends Controller
{
    private ?SimulationService $simulationService = null;
    private DatabaseService $dbService;
    private ?int $currentSessionId = null;
    
    public function __construct()
    {
        parent::__construct();
        
        session_start();
        
        $db = DatabaseManager::getInstance()->getConnection();
        $this->dbService = new DatabaseService($db);
        
        $this->restoreSimulationFromSession();
    }
    
    private function restoreSimulationFromSession(): void
    {
        if (isset($_SESSION['simulation_service']) && $_SESSION['simulation_service'] instanceof SimulationService) {
            $this->simulationService = $_SESSION['simulation_service'];
            $this->currentSessionId = $_SESSION['db_session_id'] ?? null;
            
            if ($this->currentSessionId) {
                $this->simulationService->setDatabaseService($this->dbService, $this->currentSessionId);
                $this->dbService->activateSession($this->currentSessionId);
            }
        }
    }
    
    private function saveSimulationToSession(): void
    {
        $_SESSION['simulation_service'] = $this->simulationService;
        $_SESSION['db_session_id'] = $this->currentSessionId;
    }
    
    private function createNewSimulation(array $params): void
    {
        $machines = (int)($params['machines'] ?? EnvHelper::getInt('DEFAULT_MACHINES', 50));
        $repairmen = (int)($params['repairmen'] ?? EnvHelper::getInt('DEFAULT_REPAIRMEN', 3));
        $k = (float)($params['k'] ?? EnvHelper::getFloat('DEFAULT_K_WORK', 2.2));
        $k_repair = (float)($params['k_repair'] ?? EnvHelper::getFloat('DEFAULT_K_REPAIR', 1.8));
        $step_hours = (int)($params['step_hours'] ?? EnvHelper::getInt('DEFAULT_STEP_HOURS', 1));
        $r = (float)($params['r'] ?? EnvHelper::getFloat('DEFAULT_REVENUE_RATE', 20));
        
        $k = min(10, max(0.1, $k));
        $k_repair = min(10, max(0.1, $k_repair));
        $step_hours = in_array($step_hours, [1, 8]) ? $step_hours : 1;
        
        $theta_work = WeibullCalculatorHelper::calculateTheta(MIN_WORK_TIME, MAX_WORK_TIME, $k);
        $theta_repair = WeibullCalculatorHelper::calculateTheta(MIN_REPAIR_TIME, MAX_REPAIR_TIME, $k_repair);
        
        $this->simulationService = new SimulationService(
            $machines, $repairmen, $k, $k_repair,
            $theta_work, $theta_repair, $step_hours, $r
        );
        
        $this->currentSessionId = $this->dbService->createSimulationSession(
            $machines, $repairmen, $k, $k_repair,
            $theta_work, $theta_repair, $step_hours, $r
        );
        
        $this->simulationService->setDatabaseService($this->dbService, $this->currentSessionId);
        
        $_SESSION['last_params'] = [
            'machines' => $machines,
            'repairmen' => $repairmen,
            'k' => $k,
            'k_repair' => $k_repair,
            'step_hours' => $step_hours,
            'r' => $r,
            'theta_work' => $theta_work,
            'theta_repair' => $theta_repair,
        ];
        
        $this->saveInitialState();
        
        $this->saveSimulationToSession();
    }
    
    private function saveInitialState(): void
    {
        if (!$this->simulationService || !$this->currentSessionId) {
            return;
        }
        
        $machines = $this->simulationService->getMachines();
        $working = count(array_filter($machines, fn($m) => $m->status === 'working'));
        $repair = count(array_filter($machines, fn($m) => $m->status === 'repair'));
        $waiting = count(array_filter($machines, fn($m) => $m->status === 'waiting'));
        $totalRepairs = array_sum(array_map(fn($m) => $m->repair_count, $machines));
        $totalDowntime = array_sum(array_map(fn($m) => $m->total_downtime, $machines));
        $avgRemaining = $this->simulationService->getMachinesCount() > 0 
            ? array_sum(array_map(fn($m) => $m->remaining, $machines)) / $this->simulationService->getMachinesCount() 
            : 0;
        
        try {
            $this->dbService->saveStep(
                $this->currentSessionId, 0, $this->simulationService->getStepHours(),
                $working, $repair, $waiting, 0,
                $totalRepairs, $totalDowntime, 0, 0, $avgRemaining,
                $machines, $this->simulationService->getRepairQueue()
            );
        } catch (\Exception $e) {
            error_log("Failed to save initial state: " . $e->getMessage());
        }
    }
    
    public function index(Request $request, array $params): Response
    {
        if (!$this->simulationService || !$this->currentSessionId) {
            $defaultParams = [
                'machines' => EnvHelper::getInt('DEFAULT_MACHINES', 50),
                'repairmen' => EnvHelper::getInt('DEFAULT_REPAIRMEN', 3),
                'k' => EnvHelper::getFloat('DEFAULT_K_WORK', 2.2),
                'k_repair' => EnvHelper::getFloat('DEFAULT_K_REPAIR', 1.8),
                'step_hours' => EnvHelper::getInt('DEFAULT_STEP_HOURS', 1),
                'r' => EnvHelper::getFloat('DEFAULT_REVENUE_RATE', 20),
            ];
            $this->createNewSimulation($defaultParams);
        }
        
        $lastParams = $_SESSION['last_params'] ?? [
            'machines' => EnvHelper::getInt('DEFAULT_MACHINES', 50),
            'repairmen' => EnvHelper::getInt('DEFAULT_REPAIRMEN', 3),
            'k' => EnvHelper::getFloat('DEFAULT_K_WORK', 2.2),
            'k_repair' => EnvHelper::getFloat('DEFAULT_K_REPAIR', 1.8),
            'step_hours' => EnvHelper::getInt('DEFAULT_STEP_HOURS', 1),
            'r' => EnvHelper::getFloat('DEFAULT_REVENUE_RATE', 20),
            'theta_work' => 0,
            'theta_repair' => 0,
        ];
        
        $data = [
            'simulation' => $this->simulationService,
            'results' => $this->simulationService->getMachines(),
            'repair_queue' => $this->simulationService->getRepairQueue(),
            'total_profit' => $this->simulationService->getTotalProfit(),
            'working_count' => count(array_filter($this->simulationService->getMachines(), fn($m) => $m->status === 'working')),
            'repair_count' => count(array_filter($this->simulationService->getMachines(), fn($m) => $m->status === 'repair')),
            'waiting_count' => count(array_filter($this->simulationService->getMachines(), fn($m) => $m->status === 'waiting')),
            'queue_length' => count($this->simulationService->getRepairQueue()),
            'total_repairs' => array_sum(array_map(fn($m) => $m->repair_count, $this->simulationService->getMachines())),
            'total_downtime' => array_sum(array_map(fn($m) => $m->total_downtime, $this->simulationService->getMachines())),
            'avg_remaining' => $this->simulationService->getMachinesCount() > 0 
                ? array_sum(array_map(fn($m) => $m->remaining, $this->simulationService->getMachines())) / $this->simulationService->getMachinesCount() 
                : 0,
            'session_id' => $this->currentSessionId,
            'last_params' => $lastParams,
            'theta_work' => $lastParams['theta_work'] ?? 0,
            'theta_repair' => $lastParams['theta_repair'] ?? 0,
        ];
        
        return $this->render('simulation.index', $data);
    }
    
    public function generate(Request $request, array $params): Response
    {
        $postData = $request->all();
        $this->createNewSimulation($postData);
        
        return $this->redirect('/simulation');
    }
    
    public function step(Request $request, array $params): Response
    {
        if (!$this->simulationService || !$this->currentSessionId) {
            return $this->json(['error' => 'No active simulation'], 400);
        }
        
        set_time_limit(300);
        
        try {
            $this->simulationService->makeStep();
            $this->saveSimulationToSession();
            
            if ($request->isAjax()) {
                return $this->json([
                    'success' => true,
                    'step' => $this->simulationService->getStepCounter(),
                    'profit' => $this->simulationService->getTotalProfit(),
                    'working' => count(array_filter($this->simulationService->getMachines(), fn($m) => $m->status === 'working'))
                ]);
            }
            
            return $this->redirect('/simulation');
            
        } catch (\Exception $e) {
            error_log("Step error: " . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function autoStep(Request $request, array $params): Response
    {
        if (!$this->simulationService || !$this->currentSessionId) {
            return $this->json(['error' => 'No active simulation'], 400);
        }
        
        $count = (int)$request->post('count', 1);
        $count = min(10000, max(1, $count));
        
        set_time_limit(300);
        
        try {
            $this->simulationService->makeSteps($count);
            $this->saveSimulationToSession();
            
            if ($request->isAjax()) {
                return $this->json([
                    'success' => true,
                    'steps' => $count,
                    'step' => $this->simulationService->getStepCounter(),
                    'profit' => $this->simulationService->getTotalProfit()
                ]);
            }
            
            return $this->redirect('/simulation');
            
        } catch (\Exception $e) {
            error_log("Auto step error: " . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function reset(Request $request, array $params): Response
    {
        if ($this->currentSessionId) {
            $this->dbService->completeSession($this->currentSessionId);
        }
        
        $_SESSION['simulation_service'] = null;
        $_SESSION['db_session_id'] = null;
        $this->simulationService = null;
        $this->currentSessionId = null;
        
        $defaultParams = [
            'machines' => EnvHelper::getInt('DEFAULT_MACHINES', 50),
            'repairmen' => EnvHelper::getInt('DEFAULT_REPAIRMEN', 3),
            'k' => EnvHelper::getFloat('DEFAULT_K_WORK', 2.2),
            'k_repair' => EnvHelper::getFloat('DEFAULT_K_REPAIR', 1.8),
            'step_hours' => EnvHelper::getInt('DEFAULT_STEP_HOURS', 1),
            'r' => EnvHelper::getFloat('DEFAULT_REVENUE_RATE', 20),
        ];
        $this->createNewSimulation($defaultParams);
        
        return $this->redirect('/simulation');
    }
    
    public function getState(Request $request, array $params): Response
    {
        if (!$this->simulationService) {
            return $this->json(['error' => 'No active simulation'], 404);
        }
        
        $machines = $this->simulationService->getMachines();
        
        return $this->json([
            'success' => true,
            'step' => $this->simulationService->getStepCounter(),
            'profit' => $this->simulationService->getTotalProfit(),
            'working' => count(array_filter($machines, fn($m) => $m->status === 'working')),
            'repair' => count(array_filter($machines, fn($m) => $m->status === 'repair')),
            'waiting' => count(array_filter($machines, fn($m) => $m->status === 'waiting')),
            'queue' => count($this->simulationService->getRepairQueue()),
            'session_id' => $this->currentSessionId,
            'status' => $this->currentSessionId ? $this->dbService->getSessionStatus($this->currentSessionId) : null
        ]);
    }
    
    public function clearData(Request $request, array $params): Response
    {
        $keepLast = (bool)$request->post('keep_last', false);
        
        $db = DatabaseManager::getInstance()->getConnection();
        $result = $db->deleteAllSessions($keepLast);
        
        $_SESSION['simulation_service'] = null;
        $_SESSION['db_session_id'] = null;
        $this->simulationService = null;
        $this->currentSessionId = null;
        
        $defaultParams = [
            'machines' => EnvHelper::getInt('DEFAULT_MACHINES', 50),
            'repairmen' => EnvHelper::getInt('DEFAULT_REPAIRMEN', 3),
            'k' => EnvHelper::getFloat('DEFAULT_K_WORK', 2.2),
            'k_repair' => EnvHelper::getFloat('DEFAULT_K_REPAIR', 1.8),
            'step_hours' => EnvHelper::getInt('DEFAULT_STEP_HOURS', 1),
            'r' => EnvHelper::getFloat('DEFAULT_REVENUE_RATE', 20),
        ];
        $this->createNewSimulation($defaultParams);
        
        return $this->json($result);
    }
}