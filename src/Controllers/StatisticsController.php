<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Facades\Statistics as StatisticsFacade;

class StatisticsController extends Controller
{
    public function index(Request $request, array $params): Response
    {
        $data = [
            'title' => 'Статистика моделирования',
            'dashboard' => StatisticsFacade::getDashboardSummary(),
            'hourlyStats' => StatisticsFacade::getHourlyStats(),
            'efficiencyStats' => StatisticsFacade::getEfficiencyStats(),
            'dailyStats' => StatisticsFacade::getDailyStats(7),
            'weibullStats' => StatisticsFacade::getWeibullStats(),
            'statusDistribution' => StatisticsFacade::getStatusDistribution(),
            'repairStats' => StatisticsFacade::getRepairStats()
        ];
        
        return $this->render('statistics.index', $data);
    }
    
    public function getSessions(Request $request, array $params): Response
    {
        $sessions = StatisticsFacade::getAllSessions();
        return $this->json([
            'success' => true,
            'data' => $sessions
        ]);
    }
    
    public function getSession(Request $request, array $params): Response
    {
        $sessionId = (int)($params['id'] ?? 0);
        
        if (!$sessionId) {
            return Response::notFound('Session ID required');
        }
        
        $session = StatisticsFacade::getSessionDetails($sessionId);
        
        if (!$session) {
            return Response::notFound('Session not found');
        }
        
        return $this->json($session);
    }
    
    public function getSessionSteps(Request $request, array $params): Response
    {
        $sessionId = (int)($params['id'] ?? 0);
        
        if (!$sessionId) {
            return Response::notFound('Session ID required');
        }
        
        $steps = StatisticsFacade::getSessionSteps($sessionId);
        $timeSeries = StatisticsFacade::getTimeSeriesData($sessionId);
        
        return $this->json([
            'success' => true,
            'steps' => $steps,
            'time_series' => $timeSeries
        ]);
    }
    
    public function getSessionHistory(Request $request, array $params): Response
    {
        $sessionId = (int)($params['id'] ?? 0);
        
        if (!$sessionId) {
            return Response::notFound('Session ID required');
        }
        
        $history = StatisticsFacade::getSessionHistory($sessionId);
        
        return $this->json([
            'success' => true,
            'data' => $history
        ]);
    }
    
    public function deleteSession(Request $request, array $params): Response
    {
        $sessionId = (int)($params['id'] ?? 0);
        
        if (!$sessionId) {
            return Response::notFound('Session ID required');
        }
        
        $result = StatisticsFacade::deleteSessionById($sessionId);
        return $this->json($result);
    }
    
    public function dashboard(Request $request, array $params): Response
    {
        $summary = StatisticsFacade::getDashboardSummary();
        $correlations = StatisticsFacade::getCorrelationData();
        
        $data = [
            'success' => true,
            'summary' => $summary,
            'correlations' => array_slice($correlations, 0, 20),
            'top_sessions' => $summary['top_sessions'] ?? []
        ];
        
        return $this->json($data);
    }
    
    public function correlations(Request $request, array $params): Response
    {
        $data = StatisticsFacade::getCorrelationData();
        
        $analysis = $this->analyzeCorrelations($data);
        
        return $this->json([
            'success' => true,
            'analysis' => $analysis,
            'raw_data' => array_slice($data, 0, 100)
        ]);
    }
    
    public function exportCsv(Request $request, array $params): Response
    {
        $type = $request->get('type', 'sessions');
        
        if ($type === 'sessions') {
            $data = StatisticsFacade::getAllSessions();
            $filename = 'sessions_export_' . date('Y-m-d_His') . '.csv';
            
            $csv = $this->arrayToCsv($data);
            
            return Response::file($csv, $filename);
        }
        
        return Response::notFound('Export type not found');
    }
    
    private function analyzeCorrelations(array $data): array
    {
        if (empty($data)) {
            return [];
        }
        
        $machines = array_column($data, 'machines_count');
        $repairmen = array_column($data, 'repairmen_count');
        $kWork = array_column($data, 'k_work');
        $kRepair = array_column($data, 'k_repair');
        $revenue = array_column($data, 'revenue_rate');
        $profit = array_column($data, 'avg_profit');
        
        return [
            'profit_vs_machines' => $this->pearsonCorrelation($profit, $machines),
            'profit_vs_repairmen' => $this->pearsonCorrelation($profit, $repairmen),
            'profit_vs_k_work' => $this->pearsonCorrelation($profit, $kWork),
            'profit_vs_k_repair' => $this->pearsonCorrelation($profit, $kRepair),
            'profit_vs_revenue' => $this->pearsonCorrelation($profit, $revenue),
            'sample_size' => count($data),
            'interpretation' => $this->interpretCorrelations($profit, $machines, $repairmen)
        ];
    }
    
    private function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n === 0) {
            return 0;
        }
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(function($a, $b) { return $a * $b; }, $x, $y));
        $sumX2 = array_sum(array_map(function($a) { return $a * $a; }, $x));
        $sumY2 = array_sum(array_map(function($a) { return $a * $a; }, $y));
        
        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        return $denominator != 0 ? round($numerator / $denominator, 4) : 0;
    }
    
    private function interpretCorrelations(array $profit, array $machines, array $repairmen): array
    {
        $corrMachines = $this->pearsonCorrelation($profit, $machines);
        $corrRepairmen = $this->pearsonCorrelation($profit, $repairmen);
        
        return [
            'machines_impact' => $corrMachines > 0.5 ? 'strong_positive' : ($corrMachines < -0.5 ? 'strong_negative' : 'weak'),
            'repairmen_impact' => $corrRepairmen > 0.5 ? 'strong_positive' : ($corrRepairmen < -0.5 ? 'strong_negative' : 'weak'),
            'message' => $this->getCorrelationMessage($corrMachines, $corrRepairmen)
        ];
    }
    
    private function getCorrelationMessage(float $corrMachines, float $corrRepairmen): string
    {
        if ($corrMachines > 0.7) {
            return "Сильная положительная корреляция между количеством станков и прибылью. Увеличение станков значительно повышает прибыль.";
        } elseif ($corrMachines < -0.7) {
            return "Сильная отрицательная корреляция между количеством станков и прибылью. Увеличение станков снижает прибыль.";
        } elseif ($corrRepairmen > 0.5) {
            return "Умеренная положительная корреляция между количеством ремонтников и прибылью.";
        } else {
            return "Слабая корреляция между параметрами и прибылью. Рекомендуется оптимизировать конфигурацию.";
        }
    }
    
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $tempFile = tmpfile();
        $headers = array_keys($data[0]);
        
        fputcsv($tempFile, $headers);
        
        foreach ($data as $row) {
            fputcsv($tempFile, $row);
        }
        
        rewind($tempFile);
        $content = stream_get_contents($tempFile);
        fclose($tempFile);
        
        return $content;
    }
}