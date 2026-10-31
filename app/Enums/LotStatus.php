<?php

namespace App\Enums;

enum LotStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case ARCHIVED = 'archived';
}
