<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserAddr extends Model
{
    public $table = "user_addr";

    public $fillable = ["*"];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute()
    {
        return "{$this->province}{$this->city}{$this->area}{$this->address}";
    }
}
