<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleWorkflowEvent extends Model
{
    protected $fillable = [
        'article_id', 'user_id', 'from_status', 'to_status', 'action', 'comment',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
