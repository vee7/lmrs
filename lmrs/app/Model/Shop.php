<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    public $table = "shops";

    public $fillable = ["*"];

    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
