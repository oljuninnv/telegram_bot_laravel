<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'start_date',
        'end_date',
        'sheet_url',
        'chat_id',
        'hashtag_id',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function hashtag()
    {
        return $this->belongsTo(Hashtag::class);
    }
}
