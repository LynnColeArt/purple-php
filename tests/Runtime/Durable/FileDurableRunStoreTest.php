<?php

declare(strict_types=1);

namespace Purple\Tests\Runtime\Durable;

use DateTimeImmutable;
use Purple\Runtime\Durable\DurableRunRecord;
use Purple\Runtime\Durable\FileDurableRunStore;
use Purple\Runtime\RuntimeException;
use Purple\Tests\Testing\TestCase;

final class FileDurableRunStoreTest extends TestCase
{
    public function testSavesAndLoadsDurableRunRecord(): void
    {
        $directory = sys_get_temp_dir() . '/purple-durable-runs-' . bin2hex(random_bytes(4));
        $store = new FileDurableRunStore($directory);
        $record = new DurableRunRecord(
            runId: 'run-123',
            status: 'paused',
            state: [
                'step' => 2,
                'tool_calls' => 1,
            ],
            updatedAt: new DateTimeImmutable('2026-06-04T12:00:00+00:00'),
        );

        $store->save($record);
        $loaded = $store->get('run-123');

        $this->assertNotNull($loaded);
        $this->assertSame('paused', $loaded->status);
        $this->assertSame(2, $loaded->state['step'] ?? null);
        $this->assertSame('2026-06-04T12:00:00+00:00', $loaded->updatedAt?->format(DATE_ATOM));
        $this->assertNull($store->get('missing-run'));
    }

    public function testRejectsUnsafeDurableRunId(): void
    {
        $store = new FileDurableRunStore(sys_get_temp_dir() . '/purple-durable-runs-' . bin2hex(random_bytes(4)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may only contain');

        $store->get('../run-123');
    }
}
