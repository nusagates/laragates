<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Template extends Model
{
    use HasFactory;

    // PAKAI TABEL LAMA
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'category',
        'language',
        'status',
        'remote_id',
        'header',
        'body',
        'footer',
        'buttons',
        'version',
        'created_by',
        'approved_by',
        'approved_at',
        'meta',
        'workflow_notes',
        'last_synced_at',
        'last_sent_at',
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

    public function versions()
    {
        // pakai template_id sebagai FK
        return $this->hasMany(TemplateVersion::class, 'template_id');
    }

    public function notes()
    {
        // pakai template_id sebagai FK
        return $this->hasMany(TemplateNote::class, 'template_id');
    }
}
