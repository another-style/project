<?php

namespace App\Models;

use App\Services\MarkdownService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'last_comment_id',
        'likes_count',
        'dislikes_count',
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
                // Новая тема — last_comment_at = created_at, last_comment_id = собственный id
                $comment->updateQuietly([
                    'last_comment_at' => $comment->created_at,
                    'last_comment_id' => $comment->id,
                ]);
            } else {
                // Ответ — обновить last_comment_at и last_comment_id у корневого комментария
                $root = $comment->ancestors()->whereNull('parent_id')->first();
                if ($root) {
                    $root->updateQuietly([
                        'last_comment_at' => now(),
                        'last_comment_id' => $comment->id,
                    ]);
                }
            }
        });
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CommentVote::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected function messageHtml(): Attribute
    {
        return Attribute::make(
            get: fn () => app(MarkdownService::class)->toHtml($this->message ?? ''),
        );
    }

    /**
     * Scope для получения только корневых комментариев (тем).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
