<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'recipe_id', 'order'];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
