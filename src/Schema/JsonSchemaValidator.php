<?php

declare(strict_types=1);

namespace Purple\Schema;

use JsonException;
use Purple\Contracts\Schema\SchemaValidator;
use Purple\Contracts\Schema\ValidationResult;

final class JsonSchemaValidator implements SchemaValidator
{
    public function validate(mixed $value, string $schema): ValidationResult
    {
        try {
            $decoded = json_decode($schema, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return ValidationResult::fail(['Schema is not valid JSON: ' . $exception->getMessage()]);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return ValidationResult::fail(['Schema must decode to an object.']);
        }

        /** @var array<string, mixed> $decoded */
        $violations = $this->validateAgainstSchema($value, $decoded, '$');

        if ($violations !== []) {
            return ValidationResult::fail($violations);
        }

        return ValidationResult::pass();
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    private function validateAgainstSchema(mixed $value, array $schema, string $path): array
    {
        $violations = [];
        $type = $schema['type'] ?? null;

        if (is_string($type) && ! $this->matchesType($value, $type)) {
            return [sprintf('%s must be %s.', $path, $type)];
        }

        if ($type === 'object' && is_array($value) && ! array_is_list($value)) {
            /** @var array<string, mixed> $value */
            $violations = array_merge($violations, $this->validateRequiredProperties($value, $schema, $path));
            $violations = array_merge($violations, $this->validateProperties($value, $schema, $path));
        }

        return $violations;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'array' => is_array($value) && array_is_list($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'object' => is_array($value) && ! array_is_list($value),
            'string' => is_string($value),
            default => true,
        };
    }

    /**
     * @param array<string, mixed> $value
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    private function validateRequiredProperties(array $value, array $schema, string $path): array
    {
        $required = $schema['required'] ?? [];

        if (! is_array($required)) {
            return [];
        }

        $violations = [];

        foreach ($required as $property) {
            if (! is_string($property)) {
                continue;
            }

            if (! array_key_exists($property, $value)) {
                $violations[] = sprintf('%s.%s is required.', $path, $property);
            }
        }

        return $violations;
    }

    /**
     * @param array<string, mixed> $value
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    private function validateProperties(array $value, array $schema, string $path): array
    {
        $properties = $schema['properties'] ?? [];

        if (! is_array($properties)) {
            return [];
        }

        $violations = [];

        foreach ($properties as $property => $propertySchema) {
            if (! is_string($property) || ! is_array($propertySchema) || ! array_key_exists($property, $value)) {
                continue;
            }

            /** @var array<string, mixed> $propertySchema */
            $violations = array_merge(
                $violations,
                $this->validateAgainstSchema($value[$property], $propertySchema, $path . '.' . $property),
            );
        }

        return $violations;
    }
}
