<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Node extends Model
{
    protected $table = 'nodes';
    public $timestamps = true;
    protected $fillable = ['parent', 'title', 'created_at'];

    // parent relation
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'parent');
    }

    // children relation
    public function children(): HasMany
    {
        return $this->hasMany(Node::class, 'parent');
    }
}
