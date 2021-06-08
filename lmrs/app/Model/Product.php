<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $table = "products";

    public $fillable = ["*"];

    protected $casts = [
        'status' => "boolean"
    ];

    public $timestamps = false;

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function categorys()
    {
        return $this->belongsTo(ProductCategory::class,'one_category_id');
    }

    public function description()
    {
        return $this->hasOne(ProductDescription::class);
    }
}
