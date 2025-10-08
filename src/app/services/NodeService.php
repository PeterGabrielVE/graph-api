<?php

namespace App\Services;

use App\Models\Node;
use App\Repositories\NodeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class NodeService
{
    protected NodeRepository $nodeRepo;

    public function __construct(NodeRepository $nodeRepo)
    {
        $this->nodeRepo = $nodeRepo;
    }

    /**
     * Crea un nodo, genera su título traducido y lo guarda.
     */
    public function createNode(?int $parentId, string $locale): Node
    {
        try {
            $node = $this->nodeRepo->createNode($parentId);

            // Título base en inglés
            $node->title = NumberWordService::numberToWords((int)$node->id, 'en');
            $node->save();

            // Traducción al idioma solicitado
            $node->translated_title = NumberWordService::numberToWords((int)$node->id, $locale);

            return $node;
        } catch (\Throwable $e) {
            Log::error('Error creando nodo: ' . $e->getMessage(), [
                'parent_id' => $parentId,
                'locale' => $locale,
            ]);
            throw $e;
        }
    }

    public function deleteNode(int $id): array
    {
        $node = $this->nodeRepo->findById($id);

        if (!$node) {
            return [
                'success' => false,
                'message' => 'Node not found.',
                'code' => 404,
            ];
        }

        if ($node->children()->exists()) {
            return [
                'success' => false,
                'message' => 'Cannot delete node with children.',
                'code' => 409,
            ];
        }

        try {
            $node->delete();

            return [
                'success' => true,
                'message' => 'Node deleted successfully.',
                'code' => 200,
            ];
        } catch (\Throwable $e) {
            Log::error('Error deleting node in service', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error deleting node.',
                'code' => 500,
            ];
        }
    }


    /**
     * Obtiene los hijos de un nodo hasta cierta profundidad.
     */
    public function getChildren(Node $parent, ?int $depth, string $locale, string $tz): array
    {
        try {
            if (is_null($depth)) {
                return $this->formatNodes(
                    $this->nodeRepo->getChildren($parent),
                    $locale,
                    $tz,
                    false // sin recursión
                );
            }

            if ($depth < 1) {
                throw new \InvalidArgumentException('Depth must be >= 1');
            }

            return $this->formatNodes(
                $this->nodeRepo->getChildren($parent),
                $locale,
                $tz,
                true,  // con recursión
                $depth - 1
            );

        } catch (\Throwable $e) {
            Log::error("Error obteniendo hijos del nodo {$parent->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 🔹 Método único que mapea nodos y opcionalmente incluye hijos.
     */
    private function formatNodes(
        Collection $nodes,
        string $locale,
        string $tz,
        bool $recursive = false,
        int $depth = 0
    ): array {
        return $nodes->map(function (Node $node) use ($locale, $tz, $recursive, $depth) {
            $data = [
                'id' => (int) $node->id,
                'parent' => $node->parent ? (int) $node->parent : null,
                'title' => NumberWordService::numberToWords((int) $node->id, $locale),
                'created_at' => Carbon::parse($node->created_at, 'UTC')
                    ->setTimezone($tz)
                    ->toDateTimeString(),
            ];

            // Si queremos incluir hijos recursivamente:
            if ($recursive && $depth > 0) {
                $children = $this->nodeRepo->getChildren($node);
                $data['children'] = $this->formatNodes($children, $locale, $tz, true, $depth - 1);
            }

            return $data;
        })->toArray();
    }

}
