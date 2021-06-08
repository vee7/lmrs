<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    public $table="product_skus";

    public $fillable = ["*"];

    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productattributevalue()
    {
        return $this->belongsTo(ProductAttributeValue::class);
    }

    public function decreaseStock($amount)
    {
        if ($amount < 0) {
            throw new \RuntimeException('减库存不可小于0');
        }

        return $this->where('id', $this->id)->where('num', '>=', $amount)->decrement('num', $amount);
    }

    public function addStock($amount)
    {
        if ($amount < 0) {
            throw new \RuntimeException('加库存不可小于0');
        }
        $this->increment('num', $amount);
    }
}
