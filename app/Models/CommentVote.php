<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $comment_id
 * @property string $ip_address
 * @property int $vote
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Comment $comment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote whereCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentVote whereVote($value)
 * @mixin \Eloquent
 */
class CommentVote extends Model
{
    protected $fillable = [
        'comment_id',
        'ip_address',
        'vote',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
