<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateVersion extends Model
{
    protected $table = 'template_versions';

    protected $guarded = [];

    protected $casts = [
        'buttons' => 'array'
    ];

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
