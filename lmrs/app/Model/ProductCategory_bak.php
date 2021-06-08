<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
  public $table = "product_categorys";
  public $fillable = ["*"];
}
