<?php
namespace App\Contracts;

interface Sms{
  public function send($phone,$content);
}
