<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'date',
        'origin_agency',
        'allotment',
        'document_number',
        'history_code',
        'history',
        'value',
        'history_detail',
        'description',
        'is_correct',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
