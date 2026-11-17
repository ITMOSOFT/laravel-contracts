<?php
namespace ITMOSOFT\Contracts\Tests\Feature;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use ITMOSOFT\Contracts\Tests\TestCase;
use ITMOSOFT\Contracts\Models\Contract;
use ITMOSOFT\Contracts\Models\ContractClause;
class ContractTest extends TestCase
{

    public function test_contract_can_activate_and_terminate(): void
    {
        $contract = Contract::create($this->attributes());
        $this->assertTrue($contract->activate());
        $this->assertSame('active', $contract->fresh()->status);
        $this->assertTrue($contract->fresh()->terminate('Completed'));
        $this->assertSame('terminated', $contract->fresh()->status);
        $this->assertSame('Completed', $contract->fresh()->termination_reason);
    }

    public function test_contract_renewal_creates_new_draft_and_copies_clauses(): void
    {
        $contract = Contract::create($this->attributes(['status' => 'active']));
        ContractClause::create(['contract_id' => $contract->id, 'title' => 'Confidentiality', 'body' => 'Keep data confidential']);
        $renewal = $contract->renew(['title' => 'Renewed agreement']);
        $this->assertSame('draft', $renewal->status);
        $this->assertSame($contract->id, $renewal->renewed_from_id);
        $this->assertCount(1, $renewal->clauses);
    }


    private function attributes(array $overrides = []): array
    {
        return array_merge(['reference' => (string) Str::uuid(), 'owner_type' => 'App\Models\User', 'owner_id' => 1, 'counterparty_type' => 'App\Models\User', 'counterparty_id' => 2, 'title' => 'Master Services Agreement', 'status' => 'draft'], $overrides);
    }
}
