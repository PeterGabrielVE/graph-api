<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Node;
use App\Services\NumberWordService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Repositories\NodeRepository;
use App\Http\Resources\NodeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="GRAPH API",
 *     version="1.0.0",
 *     description="API para gestionar nodos jerárquicos (árbol de nodos)"
 * )
 *
 * @OA\Tag(
 *     name="Nodes",
 *     description="Operaciones sobre nodos del árbol"
 * )
 */
class NodeController extends Controller
{
    protected NodeRepository $nodeRepo;

    public function __construct(NodeRepository $nodeRepo)
    {
        $this->nodeRepo = $nodeRepo;
    }

    /**
     * @OA\Get(
     *     path="/api/nodes",
     *     tags={"Nodes"},
     *     summary="Listar todos los nodos raíz",
     *     description="Devuelve todos los nodos sin padre (parent = null).",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de nodos raíz"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $nodes = $this->nodeRepo->getRootNodes();

            return response()->json([
                'success' => true,
                'data' => NodeResource::collection($nodes)
            ], 200);
        } catch (\Throwable $e) {
            // Log del error para debugging
            Log::error('Error al obtener nodos raíz: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los nodos.'
            ], 500);
        }
    }

        /**
     * @OA\Post(
     *     path="/api/nodes",
     *     tags={"Nodes"},
     *     summary="Crear un nuevo nodo",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id"},
     *             @OA\Property(property="id", type="integer", example=7),
     *             @OA\Property(property="parent", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Nodo creado correctamente"),
     *     @OA\Response(response=400, description="Error de validación o jerarquía")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent' => ['nullable', 'integer', 'exists:nodes,id'],
        ]);
        $node = Node::create([
            'parent' => $data['parent'] ?? null,
            'title' => null,
            'created_at' => Carbon::now('UTC')->toDateTimeString()
        ]);


        $titleEn = NumberWordService::numberToWords((int)$node->id, 'en');
        $node->title = $titleEn;
        $node->save();


        $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);
        $tz = $request->attributes->get('tz') ?: config('app.timezone', 'UTC');

        $translatedTitle = NumberWordService::numberToWords((int)$node->id, $locale);

        return response()->json([
            'id' => (int)$node->id,
            'parent' => $node->parent ? (int)$node->parent : null,
            'title' => $translatedTitle,
            'created_at' => Carbon::parse($node->created_at, 'UTC')->setTimezone($tz)->toDateTimeString(),
        ], 201);
    }

    // Listar nodos raíz (parent == null)
    public function listRoots(Request $request)
    {
        $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);
        $tz = $request->attributes->get('tz') ?: config('app.timezone','UTC');

        $roots = Node::whereNull('parent')->get();

        $result = $roots->map(function ($n) use ($locale, $tz) {
            return [
                'id' => (int)$n->id,
                'parent' => null,
                'title' => NumberWordService::numberToWords((int)$n->id, $locale),
                'created_at' => Carbon::parse($n->created_at, 'UTC')->setTimezone($tz)->toDateTimeString(),
            ];
        });

        return response()->json($result);
    }

    // Listar hijos a partir del padre con optional depth
    public function listChildren(Request $request, $parentId)
    {
        $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);
        $tz = $request->attributes->get('tz') ?: config('app.timezone','UTC');

        $depth = $request->query('depth'); // null or integer

        $parent = Node::find($parentId);
        if (!$parent) {
            return response()->json(['message' => 'Parent node not found'], 404);
        }

        // Depth not provided => direct children only
        if (is_null($depth)) {
            $children = $parent->children()->get();
            $mapped = $children->map(fn($n) => [
                'id' => (int)$n->id,
                'parent' => $n->parent ? (int)$n->parent : null,
                'title' => NumberWordService::numberToWords((int)$n->id, $locale),
                'created_at' => Carbon::parse($n->created_at, 'UTC')->setTimezone($tz)->toDateTimeString(),
            ]);
            return response()->json($mapped);
        }

        // depth provided
        $depth = (int)$depth;
        if ($depth < 1) {
            return response()->json(['message' => 'Depth must be >= 1'], 400);
        }

        // recursive retrieval limited by depth
        $result = $this->getChildrenRecursive($parent, $depth, $locale, $tz);

        return response()->json($result);
    }

    // helper recursive function to build children tree up to depth
    private function getChildrenRecursive(Node $node, int $depth, string $locale, string $tz)
    {
        if ($depth <= 0) {
            return null;
        }

        $children = $node->children()->get();

        $mapped = $children->map(function ($n) use ($depth, $locale, $tz) {
            $res = [
                'id' => (int)$n->id,
                'parent' => $n->parent ? (int)$n->parent : null,
                'title' => NumberWordService::numberToWords((int)$n->id, $locale),
                'created_at' => Carbon::parse($n->created_at, 'UTC')->setTimezone($tz)->toDateTimeString(),
            ];

            if ($depth - 1 > 0) {
                $res['children'] = $this->getChildrenRecursive($n, $depth - 1, $locale, $tz);
            } else {
                $res['children'] = [];
            }

            return $res;
        });

        return $mapped;
    }

    // Eliminar nodo (permitir sólo si no tiene hijos)
    public function destroy(Request $request, $id)
    {
        $node = Node::find($id);
        if (!$node) {
            return response()->json(['message' => 'Node not found'], 404);
        }

        $childrenCount = $node->children()->count();
        if ($childrenCount > 0) {
            return response()->json(['message' => 'Cannot delete node with children'], 409);
        }

        $node->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }
}
