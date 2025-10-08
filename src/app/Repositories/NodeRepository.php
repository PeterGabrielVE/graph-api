<?php
namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Node;
use Illuminate\Database\Eloquent\Collection;

class NodeRepository
{
    public function getRootNodes()
    {
        return Node::whereNull('parent')->get();
    }

    public function createNode(?int $parentId = null): Node
    {
        return Node::create([
            'parent' => $parentId,
            'title' => null,
            'created_at' => Carbon::now('UTC')->toDateTimeString(),
        ]);
    }

    public function findById(int $id): ?Node
    {
        return Node::find($id);
    }

    public function getChildren(Node $parent): Collection
    {
        return $parent->children()->get();
    }

    public function delete(Node $node): bool
    {
        return $node->delete();
    }
}
