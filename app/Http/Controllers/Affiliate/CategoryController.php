<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate\Category;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/affiliate/category",
     *      tags={"AFILIADO"},
     *      summary="LISTADO DE CATEGORIAS DEL AFILIADO",
     *      operationId="getCategorias",
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *          type="object"
     *          )
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     * )
     *
     * Get list of categories
     *
     * @param Request $request
     * @return void
     */

     public function index()
     {
        return Category::orderBy('name')->get();
     }

     public function show(Category $category)
     {
        return $category;
     }
}
