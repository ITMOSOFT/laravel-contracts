<?php

namespace ITMOSOFT\Contracts\Tests\Feature;

use Illuminate\Support\Str;
use ITMOSOFT\Contracts\Models\Contract;
use ITMOSOFT\Contracts\Models\ContractClause;
use ITMOSOFT\Contracts\Tests\TestCase;
use InvalidArgumentException;
use LogicException;

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
        $this->assertNotNull($contract->fresh()->terminated_at);
    }

    public function test_invalid_lifecycle_transitions_return_false(): void
    {
        $contract = Contract::create($this->attributes(['status' => 'rejected']));

        $this->assertFalse($contract->activate());
        $this->assertFalse($contract->terminate());
    }

    public function test_lifecycle_methods_reject_unsaved_contracts(): void
    {
        $contract = new Contract($this->attributes());

        $this->expectException(LogicException::class);
        $contract->activate();
    }

    public function test_contract_renewal_creates_new_draft_and_copies_clauses(): void
    {
        $contract = Contract::create($this->attributes(['status' => 'active']));
        ContractClause::create([
            'contract_id' => $contract->id,
            'title' => 'Confidentiality',
            'body' => 'Keep data confidential',
            'position' => 1,
        ]);

        $renewal = $contract->renew(['title' => 'Renewed agreement']);

        $this->assertSame('draft', $renewal->status);
        $this->assertSame($contract->id, $renewal->renewed_from_id);
        $this->assertSame(1, $renewal->renewal_count);
        $this->assertCount(1, $renewal->clauses);
        $this->assertSame('Confidentiality', $renewal->clauses->first()->title);
    }

    public function test_only_active_or_expired_contracts_can_be_renewed(): void
    {
        $contract = Contract::create($this->attributes());

        $this->expectException(InvalidArgumentException::class);
        $contract->renew();
    }

    public function test_contract_validates_status_currency_and_title(): void
    {
        foreach ([
            ['status' => 'unknown'],
            ['currency' => 'usd'],
            ['title' => '   '],
        ] as $invalid) {
            try {
                Contract::create($this->attributes($invalid));
                $this->fail('Expected invalid contract attributes to be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_contract_validates_money_and_date_order(): void
    {
        foreach ([
            ['contract_value' => '-1.00'],
            ['contract_value' => '1.999'],
            ['contract_value' => '1000000000000.00'],
            ['effective_at' => '2026-12-02', 'expires_at' => '2026-12-01'],
        ] as $invalid) {
            try {
                Contract::create($this->attributes($invalid));
                $this->fail('Expected invalid contract values to be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_contract_generates_reference_and_uses_defaults_when_omitted(): void
    {
        $attributes = $this->attributes();
        unset($attributes['reference'], $attributes['status'], $attributes['currency']);

        $contract = Contract::create($attributes);

        $this->assertTrue(Str::isUuid($contract->reference));
        $this->assertSame('draft', $contract->status);
        $this->assertSame('USD', $contract->currency);
    }

    public function test_clause_validates_required_fields(): void
    {
        $contract = Contract::create($this->attributes());

        $this->expectException(InvalidArgumentException::class);
        ContractClause::create([
            'contract_id' => $contract->id,
            'title' => '',
            'body' => 'Clause body',
        ]);
    }

    private function attributes(array $overrides = []): array
    {
        return array_merge([
            'reference' => (string) Str::uuid(),
            'owner_type' => 'App\Models\User',
            'owner_id' => 1,
            'counterparty_type' => 'App\Models\User',
            'counterparty_id' => 2,
            'title' => 'Master Services Agreement',
            'status' => 'draft',
        ], $overrides);
    }
}
