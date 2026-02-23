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

/**
 * @property int $id
 * @property int $_lft
 * @property int $_rgt
 * @property int|null $parent_id
 * @property string|null $name
 * @property string $message
 * @property string $ip_address
 * @property int $likes_count
 * @property int $dislikes_count
 * @property bool $is_pinned
 * @property \Illuminate\Support\Carbon|null $last_comment_at
 * @property int|null $last_comment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Kalnoy\Nestedset\Collection<int, Comment> $children
 * @property-read int|null $children_count
 * @property-read mixed $message_html
 * @property-read Comment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CommentImage> $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CommentVote> $votes
 * @property-read int|null $votes_count
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment ancestorsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment ancestorsOf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment applyNestedSetScope(?string $table = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment countErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment d()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment defaultOrder(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment descendantsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment fixSubtree($root)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment getNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment getPlainNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment getTotalErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment hasChildren()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment hasParent()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment isBroken()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment leaves(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment makeGap(int $cut, int $height)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment moveNode($key, $position)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment onlyTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment orWhereDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment orWhereNodeBetween($values)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment orWhereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment query()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment rebuildSubtree($root, array $data, $delete = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment rebuildTree(array $data, $delete = false, $root = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment reversed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment root(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment roots()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereAncestorOrSelf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereCreatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereDeletedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereDislikesCount($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereIpAddress($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereIsAfter($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereIsBefore($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereIsLeaf()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereIsPinned($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereIsRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereLastCommentAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereLastCommentId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereLft($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereLikesCount($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereMessage($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereName($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereNodeBetween($values, $boolean = 'and', $not = false, $query = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereParentId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereRgt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment whereUpdatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment withDepth(string $as = 'depth')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment withTrashed(bool $withTrashed = true)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Comment withoutRoot()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment withoutTrashed()
 * @mixin \Eloquent
 */
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
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'last_comment_at' => 'datetime',
            'is_pinned' => 'boolean',
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

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(CommentImage::class);
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
