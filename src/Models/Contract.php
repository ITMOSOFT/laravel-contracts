<?php

namespace ITMOSOFT\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use InvalidArgumentException;

class Contract extends Model
{
    protected $table = 'contracts';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['contract_value' => 'decimal:2', 'effective_at' => 'date', 'expires_at' => 'date', 'terminated_at' => 'date', 'metadata' => 'array',];
    }


    public function owner(): MorphTo { return $this->morphTo(); }
    public function counterparty(): MorphTo { return $this->morphTo(); }
    public function clauses(): HasMany { return $this->hasMany(ContractClause::class)->orderBy('position'); }



    public function activate(): bool
    {
        if (! in_array($this->status, ['draft', 'pending'], true)) return false;
        $this->status = 'active';
        return $this->save();
    }

    public function terminate(?string $reason = null): bool
    {
        if (! in_array($this->status, ['active', 'pending'], true)) return false;
        $this->status = 'terminated';
        $this->terminated_at = now()->toDateString();
        $this->termination_reason = $reason;
        return $this->save();
    }

    public function renew(array $attributes = []): self
    {
        if ($this->status !== 'active' && $this->status !== 'expired') {
            throw new InvalidArgumentException('Only active or expired contracts can be renewed.');
        }
        $renewal = $this->replicate(['reference', 'status', 'terminated_at', 'termination_reason']);
        $renewal->fill($attributes);
        $renewal->reference = $attributes['reference'] ?? (string) Str::uuid();
        $renewal->status = 'draft';
        $renewal->renewed_from_id = $this->id;
        $renewal->renewal_count = ((int) $this->renewal_count) + 1;
        $renewal->save();
        foreach ($this->clauses as $clause) {
            $copy = $clause->replicate();
            $copy->contract_id = $renewal->id;
            $copy->save();
        }
        return $renewal;
    }

}
