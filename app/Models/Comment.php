<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Comment extends Model
{
    use HasFactory, NodeTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'message',
        'ip_address',
        'parent_id',
        'last_comment_at',
    ];

    protected function casts(): array
    {
        return [
            'last_comment_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Comment $comment) {
            if ($comment->parent_id === null) {
                // Новая тема — last_comment_at = created_at
                $comment->updateQuietly(['last_comment_at' => $comment->created_at]);
            } else {
                // Ответ — обновить last_comment_at у корневого комментария
                $root = $comment->ancestors()->whereNull('parent_id')->first();
                if ($root) {
                    $root->updateQuietly(['last_comment_at' => now()]);
                }
            }
        });
    }

    /**
     * Scope для получения только корневых комментариев (тем).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
