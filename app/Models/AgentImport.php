<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentImport extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'import_id';

    protected $fillable = [
        'original_filename',
        'stored_filepath',
        'file_hash',
        'total_rows',
        'created_count',
        'updated_count',
        'rejected_count',
        'errors_count',
        'status',
        'error_message',
        'uploaded_by',
        'processing_duration_ms',
        'rolled_back_at',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'agent_import_id', 'import_id');
    }
}
