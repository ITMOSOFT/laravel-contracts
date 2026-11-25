<?php

namespace ITMOSOFT\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use LogicException;

class Contract extends Model
{
    public const STATUSES = ['draft', 'pending', 'active', 'expired', 'terminated', 'rejected'];

    protected $table = 'contracts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'contract_value' => 'decimal:2',
            'effective_at' => 'date',
            'expires_at' => 'date',
            'terminated_at' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $contract): void {
            if ($contract->exists === false) {
                if (blank($contract->reference)) {
                    $contract->reference = (string) Str::uuid();
                }

                $contract->status ??= 'draft';
                $contract->currency ??= config('itmosoft-contracts.default_currency', 'USD');
            }

            $contract->validateAttributes();
        });
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function counterparty(): MorphTo
    {
        return $this->morphTo();
    }

    public function clauses(): HasMany
    {
        return $this->hasMany(ContractClause::class)->orderBy('position');
    }

    public function activate(): bool
    {
        return $this->transition(['draft', 'pending'], ['status' => 'active']);
    }

    public function terminate(?string $reason = null): bool
    {
        return $this->transition(
            ['active', 'pending'],
            [
                'status' => 'terminated',
                'terminated_at' => now()->toDateString(),
                'termination_reason' => $reason,
            ]
        );
    }

    public function renew(array $attributes = []): self
    {
        $this->ensurePersisted();

        return DB::transaction(function () use ($attributes): self {
            /** @var self $source */
            $source = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! in_array($source->status, ['active', 'expired'], true)) {
                throw new InvalidArgumentException('Only active or expired contracts can be renewed.');
            }

            $renewal = $source->replicate([
                'reference',
                'status',
                'terminated_at',
                'termination_reason',
            ]);

            $renewal->fill($attributes);
            $renewal->reference = $attributes['reference'] ?? (string) Str::uuid();
            $renewal->status = 'draft';
            $renewal->terminated_at = null;
            $renewal->termination_reason = null;
            $renewal->renewed_from_id = $source->getKey();
            $renewal->renewal_count = ((int) $source->renewal_count) + 1;
            $renewal->save();

            foreach ($source->clauses()->get() as $clause) {
                $copy = $clause->replicate();
                $copy->contract_id = $renewal->getKey();
                $copy->save();
            }

            return $renewal;
        });
    }

    private function transition(array $allowedStatuses, array $changes): bool
    {
        $this->ensurePersisted();

        $changed = DB::transaction(function () use ($allowedStatuses, $changes): bool {
            /** @var self|null $contract */
            $contract = static::query()->lockForUpdate()->find($this->getKey());

            if (! $contract || ! in_array($contract->status, $allowedStatuses, true)) {
                return false;
            }

            $contract->forceFill($changes);
            $contract->save();

            return true;
        });

        if ($changed) {
            $this->refresh();
        }

        return $changed;
    }

    private function ensurePersisted(): void
    {
        if (! $this->exists || $this->getKey() === null) {
            throw new LogicException('The contract must be persisted before using lifecycle methods.');
        }
    }

    private function validateAttributes(): void
    {
        if (! is_string($this->reference) || trim($this->reference) === '' || mb_strlen($this->reference) > 36) {
            throw new InvalidArgumentException('Contract reference is required and must not exceed 36 characters.');
        }

        if (! is_string($this->title) || trim($this->title) === '' || mb_strlen($this->title) > 255) {
            throw new InvalidArgumentException('Contract title is required and must not exceed 255 characters.');
        }

        if (! in_array($this->status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Contract status is invalid.');
        }

        if (! is_string($this->currency) || ! preg_match('/^[A-Z]{3}$/', $this->currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter uppercase code.');
        }

        if ($this->contract_value !== null) {
            $value = (string) $this->getRawOriginal('contract_value');
            $value = (string) ($this->getAttributes()['contract_value'] ?? $this->contract_value);

            if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
                throw new InvalidArgumentException('Contract value must be non-negative, fit DECIMAL(14,2), and have at most two decimal places.');
            }
        }

        if ($this->effective_at && $this->expires_at && $this->expires_at->lt($this->effective_at)) {
            throw new InvalidArgumentException('Contract expiry date cannot be earlier than its effective date.');
        }

        if ($this->metadata !== null) {
            try {
                json_encode($this->metadata, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('Contract metadata must be JSON encodable.', previous: $exception);
            }
        }
    }
}
