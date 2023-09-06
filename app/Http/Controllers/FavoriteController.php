<?php

namespace App\Http\Controllers;

use App\Models\FavoriteRecipes;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.favorite', [
            'title' => "favorite",
            'active' => "favorite",
        ]);
    }

    public function fetchData($search)
    {
        $userId = auth()->user()->id;
        $recipes = RecipeIngredient::select(
            'recipes.id',
            'recipes.recipe_name',
            'recipes.description',
            'recipes.recipe_img',
            DB::raw('SUM(GREATEST(
                0, (recipe_ingredients.qty * units.value) - COALESCE(iv.total_current_qty, 0))
            ) missing_quantity'),
            DB::raw('IF(favorite_recipes.id IS NULL, 0, 1) AS is_favourited')
        )
            ->leftJoin('recipes', 'recipes.id', '=', 'recipe_ingredients.recipe_id')
            ->leftJoin(
                DB::raw("
                (SELECT
                    ingredient_variants.ingredient_types_id,
                    SUM(current_qty * units.value) AS total_current_qty
                FROM
                    ingredient_variants
                LEFT JOIN
                    units ON units.id = ingredient_variants.unit_id
                WHERE
                    user_id=$userId
                GROUP BY
                    ingredient_types_id
                ) AS iv"),
                'iv.ingredient_types_id',
                '=',
                'recipe_ingredients.ingredient_types_id'
            )
            ->leftJoin('units', 'units.id', '=', 'recipe_ingredients.unit_id')
            ->leftJoin('favorite_recipes', function ($join) use ($userId) {
                $join->on('favorite_recipes.recipe_id', '=', 'recipes.id');
                $join->on('favorite_recipes.user_id', '=', DB::raw("'$userId'"));
            })
            ->groupBy('recipes.id', 'recipes.recipe_name', 'recipes.description', 'recipe_img', 'favorite_recipes.id')
            ->orderBy('missing_quantity', 'asc')
            ->orderBy('recipes.recipe_name', 'asc')
            ->where('favorite_recipes.user_id', '=', auth()->user()->id);

        if ($search != "all") {
            $recipes = $recipes->where("recipe_name", "LIKE", "%" . $search . "%");
        }

        $recipes = $recipes->paginate(6);

        return response()->json([
            'title' => "Favorites",
            'active' => "favorite",
            'recipes' => $recipes,
        ], 200);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "recipe_id" => "unique:favorite_recipes,recipe_id,NULL,id,user_id," . auth()->user()->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->messages()
            ], 400);
        }

        $validator = $validator->validated();
        $validator["user_id"] = auth()->user()->id;

        FavoriteRecipes::create($validator);

        return response()->json([], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(FavoriteRecipes $favorite)
    {
        return "tes";
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FavoriteRecipes $favorite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FavoriteRecipes $favorite)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        FavoriteRecipes::where('recipe_id', '=', $id)->where('user_id', '=', auth()->user()->id)->delete();
        return response()->json([], 200);
    }
}
