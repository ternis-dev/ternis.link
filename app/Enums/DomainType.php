<?php

namespace App\Enums;

enum DomainType: string
{
    case Ternis = 'ternis';
    case Business = 'business';
    case Public = 'public';
    case Partner = 'partner';
}
