<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToDo extends Model
{
    protected $fillable = [
        'title', 'description', 'completed', 'user_id', 'due_date', 'priority', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}