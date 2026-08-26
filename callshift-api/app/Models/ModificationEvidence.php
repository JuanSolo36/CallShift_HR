<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModificationEvidence extends Model
{
    protected $table = 'modification_evidences';

    protected $fillable = [
        'schedule_modification_id',
        'original_name',
        'stored_filename',
        'storage_path',
        'mime_type',
        'file_size_bytes',
        'sha256_hash',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
    ];

    public function modification(): BelongsTo
    {
        return $this->belongsTo(ScheduleModification::class, 'schedule_modification_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/png', 'image/jpeg', 'image/jpg'], true);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
