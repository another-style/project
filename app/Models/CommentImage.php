<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $filename
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentImage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CommentImage extends Model
{
    protected $fillable = ['filename'];

    protected $appends = ['url'];

    public function comments(): BelongsToMany
    {
        return $this->belongsToMany(Comment::class);
    }

    /**
     * Удаляет физический файл изображения с диска и саму запись из БД.
     */
    public function deleteWithFile(): void
    {
        $dir = storage_path('app/public/comment-images');
        for ($i = 0; $i < 3; $i++) {
            $dir .= DIRECTORY_SEPARATOR . $this->filename[$i];
        }
        $filePath = $dir . DIRECTORY_SEPARATOR . $this->filename;

        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $this->delete();
    }

    /**
     * HTTP-URL к файлу изображения.
     * Строит путь по принципу DirectoryGenerator: первые 3 символа имени файла
     * образуют вложенные директории, например: /storage/comment-images/d/2/d/filename.jpg
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                $subDir = $this->filename[0] . '/' . $this->filename[1] . '/' . $this->filename[2] . '/';
                return asset('storage/comment-images/' . $subDir . $this->filename);
            }
        );
    }
}
