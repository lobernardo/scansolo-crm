<?php

namespace App\Enums;

enum InvitationState: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case Revoked = 'Revoked';
}
