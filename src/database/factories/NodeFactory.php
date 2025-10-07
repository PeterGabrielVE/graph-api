<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Node;
use Carbon\Carbon;

class NodeFactory extends Factory
{
    protected $model = Node::class;

    public function definition()
    {
        return [
            'parent' => null,
            'title' => null,
            'created_at' => Carbon::now()->subDays(rand(0, 365))->toDateTimeString(),
        ];
    }
}
