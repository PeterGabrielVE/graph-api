<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Node;
use App\Services\NodeService;
use App\Services\NumberWordService;
use App\Repositories\NodeRepository;
use App\Http\Requests\StoreNodeRequest;
use App\Http\Resources\NodeResource;

/**
 * @OA\Info(
 *     title="Graph API",
 *     version="1.0.0",
 *     description="API para gestionar nodos jerárquicos (árbol de nodos)"
 * )
 *
 * @OA\Tag(
 *     name="Nodes",
 *     description="Operaciones CRUD sobre nodos del árbol"
 * )
 */
class NodeController extends Controller
{
    public function __construct(
        protected NodeRepository $nodeRepo,
        protected NodeService $nodeService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/nodes/roots",
     *     tags={"Nodes"},
     *     summary="Listar nodos raíz",
     *     description="Obtiene todos los nodos que no tienen padre (`parent = null`).",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de nodos raíz",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="count", type="integer", example=3),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="parent", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="title", type="string", example="One"),
     *                     @OA\Property(property="created_at", type="string", example="2025-10-07 20:10:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error interno del servidor.")
     *         )
     *     )
     * )
     */
    public function listRoots(Request $request): JsonResponse
    {
        try {
            $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);
            $tz = $request->attributes->get('tz') ?: config('app.timezone', 'UTC');

            $roots = $this->nodeRepo->getRootNodes();

            $data = $roots->map(fn($node) => [
                'id' => (int) $node->id,
                'parent' => null,
                'title' => NumberWordService::numberToWords((int) $node->id, $locale),
                'created_at' => Carbon::parse($node->created_at, 'UTC')->setTimezone($tz)->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'count' => $data->count(),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al listar nodos raíz', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/nodes",
     *     tags={"Nodes"},
     *     summary="Crear un nuevo nodo",
     *     description="Crea un nodo nuevo con o sin padre.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="parent", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nodo creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se pudo crear el nodo.")
     *         )
     *     )
     * )
     */
    public function store(StoreNodeRequest $request): JsonResponse
    {
        try {
            $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);
            $parentId = $request->validated()['parent'] ?? null;

            $node = $this->nodeService->createNode($parentId, $locale);

            return response()->json([
                'success' => true,
                'data' => new NodeResource($node),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Error creando nodo', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'No se pudo crear el nodo.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/nodes/{parent}/children",
     *     tags={"Nodes"},
     *     summary="Listar hijos de un nodo",
     *     description="Devuelve los hijos directos o recursivos de un nodo padre.",
     *     @OA\Parameter(
     *         name="parent",
     *         in="path",
     *         required=true,
     *         description="ID del nodo padre",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="depth",
     *         in="query",
     *         required=false,
     *         description="Profundidad de búsqueda (por defecto hijos directos)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de hijos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="parent", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Two"),
     *                     @OA\Property(property="created_at", type="string", example="2025-10-07 20:20:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nodo padre no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Parent node not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parámetros inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Depth must be >= 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error en el servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Internal Server Error")
     *         )
     *     )
     * )
     */
    public function listChildren(Request $request, int $parentId): JsonResponse
    {
        if ($parentId <= 0) {
            return response()->json(['message' => 'Parent ID must be a positive integer'], 422);
        }

        try {
            $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);
            $tz = $request->attributes->get('tz') ?: config('app.timezone', 'UTC');
            $depth = $request->query('depth');

            $parent = $this->nodeRepo->findById($parentId);
            if (!$parent) {
                return response()->json(['message' => 'Parent node not found'], 404);
            }

            $children = $this->nodeService->getChildren($parent, $depth, $locale, $tz);

            return response()->json(['success' => true, 'data' => $children]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Error en listChildren', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/nodes/{id}",
     *     tags={"Nodes"},
     *     summary="Eliminar un nodo",
     *     description="Elimina un nodo si no tiene hijos.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del nodo a eliminar",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nodo eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Node deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nodo no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Node not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Nodo con hijos no puede eliminarse",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete node with children")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal Server Error")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->nodeService->deleteNode($id);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['code']);
        } catch (\Throwable $e) {
            Log::error('Error deleting node', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
