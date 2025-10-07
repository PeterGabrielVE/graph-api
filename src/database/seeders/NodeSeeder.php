<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Node;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class NodeSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Starting NodeSeeder...');

        try {
            // Función alternativa para convertir número a texto en inglés simple
            $numberToWords = function (int $number): string {
                $words = [
                    1 => 'one',
                    2 => 'two',
                    3 => 'three',
                    4 => 'four',
                    5 => 'five',
                    6 => 'six',
                    7 => 'seven',
                    8 => 'eight',
                    9 => 'nine',
                    10 => 'ten',
                ];
                return $words[$number] ?? (string)$number;
            };

            $createNode = function (?int $parentId, int $idNumber) use ($numberToWords): ?Node {
                if ($parentId !== null && !Node::find($parentId)) {
                    Log::warning("Parent node with ID {$parentId} not found. Skipping child {$idNumber}.");
                    return null;
                }

                if (Node::find($idNumber)) {
                    Log::warning("Node with ID {$idNumber} already exists. Skipping.");
                    return null;
                }

                $title = ucfirst($numberToWords($idNumber));

                $node = Node::create([
                    'id' => $idNumber,
                    'parent' => $parentId,
                    'title' => $title,
                    'created_at' => Carbon::now()->subDays(rand(1, 15))->toDateTimeString(),
                ]);

                Log::info("Node created: [id={$node->id}, title={$node->title}, parent={$node->parent}]");
                return $node;
            };

            // Estructura del árbol
            $root1 = $createNode(null, 1);
            $root2 = $createNode(null, 2);
            $child1 = $createNode($root1?->id, 3);
            $child2 = $createNode($root1?->id, 4);
            $createNode($child1?->id, 5);
            $createNode($root2?->id, 6);

            Log::info('NodeSeeder completed successfully.');
        } catch (Throwable $e) {
            Log::error('Error in NodeSeeder: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
