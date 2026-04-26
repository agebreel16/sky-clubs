<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataImport extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'import_id';

    protected $fillable = [
        'data_date',
        'source_type',
        'original_filename',
        'stored_filepath',
        'file_hash',
        'total_agents',
        'processed',
        'rejected',
        'promotions_count',
        'demotions_count',
        'warnings_count',
        'errors_count',
        'status',
        'error_message',
        'uploaded_by',
        'processed_by',
        'processing_duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'data_date' => 'date',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by', 'id');
    }

    public function dailySnapshots(): HasMany
    {
        return $this->hasMany(DailySnapshot::class, 'import_id', 'import_id');
    }
}
