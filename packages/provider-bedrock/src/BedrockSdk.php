<?php

declare(strict_types=1);

namespace Purple\Provider\Bedrock;

use InvalidArgumentException;
use Purple\Audit\FileAuditLog;
use Purple\Contracts\Audit\AuditLog;
use Purple\Contracts\Policy\PolicyEngine;
use Purple\Contracts\Schema\SchemaValidator;
use Purple\ProviderProfile;
use Purple\Sdk;

final readonly class BedrockSdk
{
    /**
     * @param null|callable(string, string, array<string, string>, array<string, mixed>): array<string, mixed> $transport
     */
    public static function create(
        ?ProviderProfile $profile = null,
        ?AuditLog $auditLog = null,
        ?PolicyEngine $policy = null,
        ?SchemaValidator $validator = null,
        ?callable $transport = null,
        string $region = 'us-east-1',
    ): Sdk {
        $profile ??= ProviderProfile::bedrock();

        if ($profile->providerName !== 'bedrock') {
            throw new InvalidArgumentException(sprintf(
                'Bedrock SDK factory requires provider profile "bedrock"; received "%s".',
                $profile->providerName,
            ));
        }

        return Sdk::fromProvider(
            provider: new BedrockProvider(
                region: $region,
                transport: $transport,
            ),
            profile: $profile,
            auditLog: $auditLog ?? new FileAuditLog($profile->auditPath ?? self::defaultAuditPath()),
            policy: $policy,
            validator: $validator,
        );
    }

    private static function defaultAuditPath(): string
    {
        $basePath = getcwd();

        if ($basePath === false) {
            $basePath = sys_get_temp_dir();
        }

        return $basePath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR . 'purple-bedrock.jsonl';
    }
}
