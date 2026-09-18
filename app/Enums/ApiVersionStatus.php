<?php

namespace App\Enums;

enum ApiVersionStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Retired = 'retired';
}
