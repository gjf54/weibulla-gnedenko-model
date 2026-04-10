<?php

namespace App\Models;

class RepairJob {
    
    public float $repair_remaining;

    public function __construct(
        public int $machine_num,
        public float $repair_time,
        public float $u_repair,
        public int $added_step,
    ) 
    {
        $this->repair_remaining = $this->repair_time; 
    }
    
    public function toArray(): array 
    {
        return [
            'machine_num' => $this->machine_num,
            'repair_time' => $this->repair_time,
            'repair_remaining' => $this->repair_remaining,
            'u_repair' => $this->u_repair,
            'added_step' => $this->added_step
        ];
    }
    
    public static function fromArray(array $data): self 
    {
        $job = new self($data['machine_num'], $data['repair_time'], $data['u_repair'], $data['added_step']);
        $job->repair_remaining = $data['repair_remaining'];
        return $job;
    }
}