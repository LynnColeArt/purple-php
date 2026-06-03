<?php

declare(strict_types=1);

namespace Purple\Tests;

use InvalidArgumentException;
use Purple\Contracts\Policy\PolicyRequest;
use Purple\ProviderProfile;
use Purple\Tests\Testing\TestCase;

final class ProviderProfileTest extends TestCase
{
    public function testCreatesFakeProfileWithPolicyDefaults(): void
    {
        $profile = ProviderProfile::fake(auditPath: 'var/audit/fake.jsonl');
        $decision = $profile->policy()->decide(new PolicyRequest(
            operation: 'smart_function.run',
            provider: 'fake',
            model: 'fake-model',
        ));

        $this->assertSame('fake', $profile->providerName);
        $this->assertSame('fake-model', $profile->model);
        $this->assertNull($profile->secretName);
        $this->assertSame('var/audit/fake.jsonl', $profile->auditPath);
        $this->assertTrue($decision->allowed);
    }

    public function testCreatesOpenAIProfileWithSecretName(): void
    {
        $profile = ProviderProfile::openAI(model: 'gpt-test', secretName: 'PURPLE_OPENAI_KEY');

        $this->assertSame('openai', $profile->providerName);
        $this->assertSame('gpt-test', $profile->model);
        $this->assertSame('PURPLE_OPENAI_KEY', $profile->secretName);
    }

    public function testRejectsBlankSecretName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret name must not be empty.');

        ProviderProfile::openAI(secretName: ' ');
    }
}
