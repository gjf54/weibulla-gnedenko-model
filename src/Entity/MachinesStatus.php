<?php

namespace App\Entity;

enum MachinesStatus: string
{
    case WORKING = 'working';
    case WAITING = 'waiting';
    case REPAIR = 'repair';
}
