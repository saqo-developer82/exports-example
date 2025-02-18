<?php

namespace Exports\Tests;

use Exports\Models\{
    Update,
    Comment,
    Customer,
    Order,
    Invoice,
    Item,
    Payment
};

use Exports\Events\EntityExported;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow('2012-12-12 12:12:12');
    }

    public function exportEntitesProvider(): array
    {
        return [
            [Invoice::class, 'invoice'],
            [Comment::class, 'comment'],
            [Customer::class, 'customer'],
            [Item::class, 'item'],
            [Update::class, 'update'],
            [Payment::class, 'payment'],
            [Order::class, 'order'],
        ];
    }

    /**
     * @dataProvider exportEntitesProvider
     */
    public function testExportInvoice($entity, $name): void
    {
        $entity = factory($entity)->create();
        Event::fake();

        $this->actingAsUser($entity->company->founder)
            ->post('/v2.5/export/email', [
                'entity' => $name,
                'entity_ids' => [$entity->id],
            ])
            ->assertStatus(200)
            ->assertJson([
                'error' => false,
                'email_sent' => true,
            ]);

        Event::assertDispatched(EntityExported::class);
    }
}
