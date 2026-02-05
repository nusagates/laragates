<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class WaMenu extends Model
{
protected $fillable = [
'parent_id',
'key',
'title',
'reply_text',
'action_type',
'is_active',
'order',
];


public function children()
{
return $this->hasMany(self::class, 'parent_id')->orderBy('order');
}
}
