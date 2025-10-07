<?php

namespace App\Services;

use App\Models\Node;
use App\Repositories\NodeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
}
