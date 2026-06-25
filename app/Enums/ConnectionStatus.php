<?php

namespace App\Enums;

enum ConnectionStatus: string
{
    case Connected = 'Connected';
    case Disconnected = 'Disconnected';
}
