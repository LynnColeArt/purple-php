<?php

declare(strict_types=1);

namespace Purple\SmartFunction;

use JsonException;
use Purple\Contracts\Audit\AuditEvent;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\Contracts\Provider\ProviderRequest;
use Purple\Contracts\Schema\ValidationResult;

final class SmartFunctionRunner
{
    public function run(SmartFunctionDefinition $function, mixed $input): SmartFunctionResult
    {
        $runId = bin2hex(random_bytes(8));
        $inputMap = $this->inputMap($input);
        $policyDecision = $function->policy->decide(new PolicyRequest(
            operation: 'smart_function.run',
            provider: $function->providerName,
            model: $function->model,
            metadata: [
                'smart_function' => $function->name(),
                ...$function->metadata,
            ],
        ));

        $function->auditLog->record(AuditEvent::now('smart_function.policy_decided', $runId, [
            'smart_function' => $function->name(),
            'provider' => $function->providerName,
            'model' => $function->model,
            'allowed' => $policyDecision->allowed,
            'reason' => $policyDecision->reason,
            ...$function->metadata,
        ]));

        if (! $policyDecision->allowed) {
            $function->auditLog->record(AuditEvent::now('smart_function.failed', $runId, [
                'smart_function' => $function->name(),
                'provider' => $function->providerName,
                'model' => $function->model,
                'status' => 'policy_denied',
                ...$function->metadata,
            ]));

            throw new PolicyDenied($policyDecision->reason ?? 'Policy denied smart function execution.');
        }

        $promptInput = $function->redactor?->redact($inputMap) ?? $inputMap;
        $prompt = $function->prompt->render($this->inputMap($promptInput));

        $function->auditLog->record(AuditEvent::now('smart_function.started', $runId, [
            'smart_function' => $function->name(),
            'provider' => $function->providerName,
            'model' => $function->model,
            ...$function->metadata,
        ]));

        $lastValidation = null;
        $lastContent = '';

        for ($attempt = 1; $attempt <= $function->maxRetries + 1; $attempt++) {
            $response = $function->provider->complete(ProviderRequest::fromPrompt($function->model, $prompt, [
                'smart_function' => $function->name(),
                'run_id' => $runId,
                'attempt' => $attempt,
                ...$function->metadata,
            ]));
            $lastContent = $response->content;

            try {
                $output = $this->decodeProviderOutput($response->content);
                $lastValidation = $function->validator->validate($output, $function->outputSchema);
            } catch (JsonException $exception) {
                $lastValidation = ValidationResult::fail([
                    'Provider output was not valid JSON: ' . $exception->getMessage(),
                ]);

                continue;
            }

            if ($lastValidation->valid) {
                $function->auditLog->record(AuditEvent::now('smart_function.completed', $runId, [
                    'smart_function' => $function->name(),
                    'provider' => $function->providerName,
                    'model' => $function->model,
                    'status' => 'completed',
                    'validation_status' => 'passed',
                    'attempts' => $attempt,
                    ...$function->metadata,
                ]));

                return new SmartFunctionResult($output, $response->content, $lastValidation, $runId, $attempt);
            }
        }

        $failedValidation = $lastValidation ?? ValidationResult::fail(['invalid JSON output: ' . $lastContent]);

        $function->auditLog->record(AuditEvent::now('smart_function.failed', $runId, [
            'smart_function' => $function->name(),
            'provider' => $function->providerName,
            'model' => $function->model,
            'status' => 'validation_failed',
            'validation_status' => 'failed',
            'violations' => $failedValidation->violations,
            ...$function->metadata,
        ]));

        throw new SchemaValidationFailed(sprintf(
            'Provider output failed schema validation: %s',
            implode('; ', $failedValidation->violations),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function inputMap(mixed $input): array
    {
        if (! is_array($input)) {
            return ['input' => $input];
        }

        if (array_is_list($input)) {
            return ['input' => $input];
        }

        /** @var array<string, mixed> $input */
        return $input;
    }

    private function decodeProviderOutput(string $content): mixed
    {
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
