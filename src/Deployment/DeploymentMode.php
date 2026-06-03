<?php

declare(strict_types=1);

namespace Purple\Deployment;

enum DeploymentMode: string
{
    case Composer = 'composer';
    case Sidecar = 'sidecar';
    case NativeExtension = 'native_extension';
}
