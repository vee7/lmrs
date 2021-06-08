<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    public $table = "user_login_log";

    protected $fillable = [
        'user_id', 'login_time', 'login_ip','login_type',
    ];

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
