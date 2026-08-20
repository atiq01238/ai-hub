<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityReaction extends Model
{
    protected $fillable = [
        'user_id',
        'reactable_type',
        'reactable_id',
        'reaction',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
