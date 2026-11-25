<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('reference')->unique();
            $table->morphs('owner');
            $table->morphs('counterparty');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->decimal('contract_value', 14, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('effective_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->unsignedInteger('renewal_count')->default(0);
            $table->foreignId('renewed_from_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_clauses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_clauses');
        Schema::dropIfExists('contracts');
    }
};
