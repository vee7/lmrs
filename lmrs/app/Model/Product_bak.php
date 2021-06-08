<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
  public $table = "products";
  public $fillable = ["*"];
}
