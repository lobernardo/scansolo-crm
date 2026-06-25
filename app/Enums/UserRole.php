<?php

namespace App\Enums;

enum UserRole: string
{
    case BusinessOwner = 'Business Owner';
    case Salesperson = 'Salesperson';
}
