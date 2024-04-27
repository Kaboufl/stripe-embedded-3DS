<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'intent_id',
        'is_paid'
    ];

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
