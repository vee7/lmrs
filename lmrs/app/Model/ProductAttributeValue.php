<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductAttributeValue extends Pivot
{
    public $fillable = ["*"];

    public $timestamps = false;
}
