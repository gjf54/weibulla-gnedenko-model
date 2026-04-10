<?php
namespace Config;

use App\Helpers\EnvHelper;


EnvHelper::load();

// Limitations
if (!defined('MIN_WORK_TIME')) {
    define('MIN_WORK_TIME', EnvHelper::getInt('MIN_WORK_TIME', 132));
}
if (!defined('MAX_WORK_TIME')) {
    define('MAX_WORK_TIME', EnvHelper::getInt('MAX_WORK_TIME', 183));
}
if (!defined('MIN_REPAIR_TIME')) {
    define('MIN_REPAIR_TIME', EnvHelper::getInt('MIN_REPAIR_TIME', 4));
}
if (!defined('MAX_REPAIR_TIME')) {
    define('MAX_REPAIR_TIME', EnvHelper::getInt('MAX_REPAIR_TIME', 10));
}
if (!defined('U_MIN')) {
    define('U_MIN', EnvHelper::getFloat('U_MIN', 0.001));
}
if (!defined('U_MAX')) {
    define('U_MAX', EnvHelper::getFloat('U_MAX', 0.999));
}

// Profit settings
if (!defined('PROFIT_PENALTY_PER_MACHINE')) {
    define('PROFIT_PENALTY_PER_MACHINE', EnvHelper::getInt('PROFIT_PENALTY_PER_MACHINE', 30));
}
if (!defined('PROFIT_REPAIRMEN_COST')) {
    define('PROFIT_REPAIRMEN_COST', EnvHelper::getFloat('PROFIT_REPAIRMEN_COST', 3.75));
}
if (!defined('PROFIT_SHORTAGE_PENALTY')) {
    define('PROFIT_SHORTAGE_PENALTY', EnvHelper::getInt('PROFIT_SHORTAGE_PENALTY', 20));
}

// Default params
if (!defined('DEFAULT_MACHINES')) {
    define('DEFAULT_MACHINES', EnvHelper::getInt('DEFAULT_MACHINES', 50));
}
if (!defined('DEFAULT_REPAIRMEN')) {
    define('DEFAULT_REPAIRMEN', EnvHelper::getInt('DEFAULT_REPAIRMEN', 3));
}
if (!defined('DEFAULT_K_WORK')) {
    define('DEFAULT_K_WORK', EnvHelper::getFloat('DEFAULT_K_WORK', 2.2));
}
if (!defined('DEFAULT_K_REPAIR')) {
    define('DEFAULT_K_REPAIR', EnvHelper::getFloat('DEFAULT_K_REPAIR', 1.8));
}
if (!defined('DEFAULT_STEP_HOURS')) {
    define('DEFAULT_STEP_HOURS', EnvHelper::getInt('DEFAULT_STEP_HOURS', 1));
}
if (!defined('DEFAULT_REVENUE_RATE')) {
    define('DEFAULT_REVENUE_RATE', EnvHelper::getFloat('DEFAULT_REVENUE_RATE', 20));
}