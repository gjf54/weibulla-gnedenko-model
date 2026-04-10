<?php
namespace App\Facades;

class Statistics {
    private static ?DB $db = null;
    
    private static function getDB(): DB 
    {
        if (self::$db === null) 
            {
            self::$db = new DB();
        }
        return self::$db;
    }
    
    public static function getAllSessions(): array 
    {
        return self::getDB()->getAllSessions();
    }
    
    public static function getStepsStats(): array 
    {
        return self::getDB()->getStepsStats();
    }
    
    public static function getParameterStats(): array 
    {
        return self::getDB()->getParameterStats();
    }
    
    public static function getTopProfitSessions(int $limit = 10): array 
    {
        return self::getDB()->getTopProfitSessions($limit);
    }
    
    public static function getDashboardSummary(): array 
    {
        return self::getDB()->getDashboardSummary();
    }
    
    public static function getSessionDetails(int $sessionId): ?array 
    {
        return self::getDB()->getSessionDetails($sessionId);
    }
    
    public static function getSessionSteps(int $sessionId): array 
    {
        return self::getDB()->getSessionSteps($sessionId);
    }
    
    public static function getTimeSeriesData(int $sessionId): array 
    {
        return self::getDB()->getTimeSeriesData($sessionId);
    }
    
    public static function getHourlyStats(): array 
    {
        return self::getDB()->getHourlyStats();
    }
    
    public static function getCorrelationData(): array 
    {
        return self::getDB()->getCorrelationData();
    }
    
    public static function getEfficiencyStats(): array 
    {
        return self::getDB()->getEfficiencyStats();
    }
    
    public static function getWeibullStats(): array 
    {
        return self::getDB()->getWeibullStats();
    }
    
    public static function getDailyStats(int $days = 30): array 
    {
        return self::getDB()->getDailyStats($days);
    }
    
    public static function getStatusDistribution(): array 
    {
        return self::getDB()->getStatusDistribution();
    }
    
    public static function getRepairStats(): array 
    {
        return self::getDB()->getRepairStats();
    }
    
    public static function getFinalMachineState(int $sessionId): array 
    {
        return self::getDB()->getFinalMachineState($sessionId);
    }
    
    public static function getSessionHistory(int $sessionId): array 
    {
        return self::getDB()->getSessionHistory($sessionId);
    }
    
    public static function deleteSessionById(int $sessionId): array 
    {
        return self::getDB()->deleteSessionById($sessionId);
    }
    
    public static function deleteOldSessions(int $days = 30): array 
    {
        return self::getDB()->deleteOldSessions($days);
    }
    
    public static function close(): void 
    {
        if (self::$db) {
            self::$db->close();
            self::$db = null;
        }
    }
}