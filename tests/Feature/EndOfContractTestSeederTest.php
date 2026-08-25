<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use Database\Seeders\EndOfContractTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndOfContractTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_three_repeatable_end_of_contract_scenarios(): void
    {
        $this->seed(EndOfContractTestSeeder::class);
        $this->seed(EndOfContractTestSeeder::class);

        $contracts = Contract::query()->where('contract_code', 'like', 'EOC-%')->get()->keyBy('contract_code');

        $this->assertCount(3, $contracts);
        $this->assertSame(today()->addDays(7)->toDateString(), $contracts['EOC-01-SAP-HET-HAN-GIA-HAN']->end_date->toDateString());
        $this->assertSame(today()->toDateString(), $contracts['EOC-02-DUNG-NGAY-HET-HAN']->end_date->toDateString());
        $this->assertSame(today()->addMonthsNoOverflow(6)->toDateString(), $contracts['EOC-03-CHAM-DUT-TRUOC-HAN']->end_date->toDateString());
        $this->assertTrue($contracts->every(fn (Contract $contract) => $contract->status === Contract::STATUS_ACTIVE));

        $extension = ContractExtensionRequest::query()->sole();
        $this->assertSame(ContractExtensionRequest::STATUS_PENDING, $extension->status);
        $this->assertTrue($extension->requested_end_date->gt($extension->current_end_date));

        $departures = ContractTerminationRequest::query()->orderBy('requested_end_date')->get();
        $this->assertCount(2, $departures);
        $this->assertTrue($departures->every(fn (ContractTerminationRequest $request) => $request->requested_end_date->isToday()));
        $this->assertSame(
            [ContractTerminationRequest::TYPE_EARLY_TERMINATION, ContractTerminationRequest::TYPE_END_OF_TERM],
            $departures->pluck('request_type')->sort()->values()->all()
        );
        $this->assertDatabaseCount('contract_lifecycle_alerts', 3);
        $this->assertDatabaseCount('utility_readings', 3);
        $this->assertDatabaseCount('invoices', 3);
        $this->assertDatabaseCount('payments', 3);
        $extensionUser = $contracts['EOC-01-SAP-HET-HAN-GIA-HAN']->tenant->user;
        $this->assertCount(1, $extensionUser->notifications);
        $this->assertSame('contract_expiring', $extensionUser->notifications->sole()->data['type']);
        $this->assertSame($contracts['EOC-01-SAP-HET-HAN-GIA-HAN']->id, $extensionUser->notifications->sole()->data['contract_id']);
    }
}
