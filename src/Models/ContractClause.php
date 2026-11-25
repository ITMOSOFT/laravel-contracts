<?php

namespace ITMOSOFT\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use JsonException;

class ContractClause extends Model
{
    protected $table = 'contract_clauses';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $clause): void {
            if (! is_string($clause->title) || trim($clause->title) === '' || mb_strlen($clause->title) > 255) {
                throw new InvalidArgumentException('Clause title is required and must not exceed 255 characters.');
            }

            if (! is_string($clause->body) || trim($clause->body) === '') {
                throw new InvalidArgumentException('Clause body is required.');
            }

            if ((int) $clause->position < 0) {
                throw new InvalidArgumentException('Clause position cannot be negative.');
            }

            if ($clause->metadata !== null) {
                try {
                    json_encode($clause->metadata, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new InvalidArgumentException('Clause metadata must be JSON encodable.', previous: $exception);
                }
            }
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
