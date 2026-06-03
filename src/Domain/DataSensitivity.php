<?php

declare(strict_types=1);

namespace Purple\Domain;

enum DataSensitivity: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';
}
