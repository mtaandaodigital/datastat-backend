<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentEmailLog extends Model
{
    use HasFactory;

    protected $table = 'sent_email_logs';

    protected $fillable = [
        'template_id',
        'registrant_id',
        'admin_id',
        'extra_note',
        'subject',
        'body_html',
    ];

    public function registrant()
    {
        return $this->belongsTo(Registrant::class, 'registrant_id', 'registrants_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
