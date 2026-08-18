<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'comparable_type', 'item_ids', 'views', 'status',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'views'    => 'integer',
    ];

    /**
     * Fetch the actual Tool or AiModel rows this comparison points to,
     * in the order they were selected.
     */
    public function items()
    {
        $modelClass = $this->comparable_type === 'tool' ? Tool::class : AiModel::class;

        $rows = $modelClass::whereIn('id', $this->item_ids)->get()->keyBy('id');

        // Re-order to match item_ids, since whereIn() doesn't guarantee order.
        return collect($this->item_ids)->map(fn ($id) => $rows->get($id))->filter()->values();
    }
}
