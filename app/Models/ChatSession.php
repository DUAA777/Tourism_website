<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'context_payload',
    ];

    protected $casts = [
        'context_payload' => 'array',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
