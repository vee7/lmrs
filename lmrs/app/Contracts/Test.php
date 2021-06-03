<?php
namespace App\Contracts;

interface Test{
  public function reg();
  public function show();
  public function get($id);
  public function del($id);
  public function edit($id);
}
?>
