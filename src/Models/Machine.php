<?php

namespace App\Models;

class Machine {
    
    public int $num;
    public float $u_work;
    public float $total_lifetime;
    public float $remaining;
    public string $status;
    public float $repair_time;
    public float $repair_remaining;
    public ?int $failure_step;
    public int $repair_count;
    public float $total_downtime;
    public array $history;
    
    public function __construct(int $num, float $u_work, float $total_lifetime) 
    {
        $this->num = $num;
        $this->u_work = $u_work;
        $this->total_lifetime = $total_lifetime;
        $this->remaining = $total_lifetime;
        $this->status = 'working';
        $this->repair_time = 0;
        $this->repair_remaining = 0;
        $this->failure_step = null;
        $this->repair_count = 0;
        $this->total_downtime = 0;
        $this->history = [];
    }
    
    public function toArray(): array 
    {
        return [
            'num' => $this->num,
            'u_work' => $this->u_work,
            'total_lifetime' => $this->total_lifetime,
            'remaining' => $this->remaining,
            'status' => $this->status,
            'repair_time' => $this->repair_time,
            'repair_remaining' => $this->repair_remaining,
            'failure_step' => $this->failure_step,
            'repair_count' => $this->repair_count,
            'total_downtime' => $this->total_downtime,
            'history' => $this->history
        ];
    }
    
    public static function fromArray(array $data): self 
    {
        $machine = new self($data['num'], $data['u_work'], $data['total_lifetime']);
        $machine->remaining = $data['remaining'];
        $machine->status = $data['status'];
        $machine->repair_time = $data['repair_time'] ?? 0;
        $machine->repair_remaining = $data['repair_remaining'] ?? 0;
        $machine->failure_step = $data['failure_step'] ?? null;
        $machine->repair_count = $data['repair_count'];
        $machine->total_downtime = $data['total_downtime'];
        $machine->history = $data['history'] ?? [];

        return $machine;
    }
}