<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','category','language','status','remote_id',
        'header','body','footer','buttons','version',
        'created_by','approved_by','approved_at','meta',
    ];

    protected $casts = [
        'header' => 'array',
        'buttons' => 'array',
        'meta' => 'array',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class,'approved_by');
    }
}
