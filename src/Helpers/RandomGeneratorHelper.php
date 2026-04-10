<?php

namespace App\Helpers;

class RandomGeneratorHelper {

    private static ?RandomGeneratorHelper $instance = null;
    
    private $state;
    
    private $seed;
    
    private const A = 13; 
    private const B = 17; 
    private const C = 5;  
    
    private function __construct($seed = null) 
    {
        $this->setSeed($seed ?? (int)(microtime(true) * 1000000));
    }
    
    public final function __clone() {}
    
    public final function __wakeup() {}
    
    public static function getInstance($seed = null): RandomGeneratorHelper
    {
        if (self::$instance === null) {
            self::$instance = new self($seed);
        }
        return self::$instance;
    }
    
    public function reset(mixed $seed = null): void 
    {
        $this->setSeed($seed ?? (int)(microtime(true) * 1000000));
    }
    

    private function setSeed(mixed $seed) 
    {
        $this->seed = $seed;
        $this->state = $this->seed;
        
        for ($i = 0; $i < 64; $i++) {
            $this->nextInt();
        }
    }
    
    private function nextInt() 
    {
        $this->state ^= ($this->state << self::A);
        $this->state ^= ($this->state >> self::B);
        $this->state ^= ($this->state << self::C);
        
        return $this->state & 0xFFFFFFFF;
    }
    
    public function generateU(): float 
    {
        return $this->nextInt() / 4294967295.0;
    }

    public static function getU(): float
    {
        return self::getInstance()->generateU();
    }
    
    public function generateRange(float $min, float $max): float 
    {
        return $min + $this->generateU() * ($max - $min);
    }
    

    public function getSeed(): mixed 
    {
        return $this->seed;
    }
}