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
    ];

    /**
     * Scope для получения только корневых комментариев (тем).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
