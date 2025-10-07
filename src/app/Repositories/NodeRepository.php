<?php
namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Node;

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
}
