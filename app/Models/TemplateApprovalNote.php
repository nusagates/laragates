<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateApprovalNote extends Model
{
    protected $table = 'template_approval_notes';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
