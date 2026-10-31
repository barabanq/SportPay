<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case BANNED = 'banned';
    case UNVERIFIED = 'unverified';
}
