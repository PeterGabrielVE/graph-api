<?php
namespace App\Repositories;

use App\Models\Node;

class NodeRepository
{
    public function getRootNodes()
    {
        return Node::whereNull('parent')->get();
    }
}
