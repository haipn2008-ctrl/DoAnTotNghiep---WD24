<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractTenant;
use App\Services\ContractRateResolver;
use Database\Seeders\BusinessScenarioSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederNewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_represents_the_new_appendix_and_room_capacity_flows(): void
    {
        $this->seed(DemoSeeder::class);

        $fullContract = Contract::query()
            ->with('room')
            ->where('contract_code', 'QA-05-ACTIVE-UNPAID')
            ->sole();

        $this->assertSame(2, $fullContract->members()->where('status', ContractTenant::STATUS_CHECKED_IN)->count());
        $this->assertSame(1, $fullContract->members()->where('status', ContractTenant::STATUS_PENDING)->count());
        $this->assertSame(3, $fullContract->capacityOccupancyCount());
        $this->assertSame(3, (int) $fullContract->room->max_people);
        $this->assertFalse($fullContract->hasRoomForMembers());

        $priceContract = Contract::query()->where('contract_code', 'QA-07-ACTIVE-PAID')->sole();
        $acceptedAppendix = $priceContract->appendices()
            ->where('status', ContractAppendix::STATUS_ACCEPTED)
            ->sole();

        $this->assertTrue($priceContract->hasValidContentSnapshot());
        $this->assertTrue($acceptedAppendix->hasValidContentHash());
        $this->assertNotEmpty($acceptedAppendix->price_adjustments);

        $resolver = app(ContractRateResolver::class);
        $before = $resolver->forPeriod($priceContract, $acceptedAppendix->effective_from->copy()->subMonth());
        $after = $resolver->forPeriod($priceContract, $acceptedAppendix->effective_from);

        $this->assertSame((float) $priceContract->electric_price_snapshot, $before->electric_price);
        $this->assertSame(
            (float) $acceptedAppendix->price_adjustments['electric_price']['new'],
            $after->electric_price,
        );

        $this->assertDatabaseHas('contract_appendices', [
            'contract_id' => Contract::query()->where('contract_code', 'QA-06-ACTIVE-PARTIAL')->value('id'),
            'status' => ContractAppendix::STATUS_PENDING_TENANT,
        ]);
        $this->assertDatabaseHas('contract_appendices', [
            'contract_id' => $fullContract->id,
            'status' => ContractAppendix::STATUS_REJECTED,
        ]);

        $this->seed(BusinessScenarioSeeder::class);
        $this->assertSame(3, ContractAppendix::query()->count());
    }
}
