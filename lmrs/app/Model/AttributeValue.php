<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    public $fillable = ["*"];

    public $timestamps = false;

    public function attribute()
    {
        return $this->belongsToMany(Attribute::class)->using(ProductAttributeValue::class);
    }
}
