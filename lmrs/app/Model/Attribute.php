<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    public $fillable = ["*"];

    public $timestamps = false;

    public function attributevalue()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
