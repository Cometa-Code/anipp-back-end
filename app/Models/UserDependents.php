<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserDependents extends Model
{
    use HasFactory;

    protected $fillable = [
        'responsible_user_id',
        'name',
        'phone',
        'email',
        'degree_of_kinship',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
