<?php
namespace ITMOSOFT\Contracts\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ContractClause extends Model {
 protected $table='contract_clauses'; protected $guarded=[];
 protected function casts(): array { return ['metadata'=>'array']; }
 public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
}
