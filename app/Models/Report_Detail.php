<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report_Detail extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'report_id',
        'chat_id',
        'hashtag_id',
    ];

    protected $table = 'report_details';

    protected $guarded = [];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function hashtag()
    {
        return $this->belongsTo(Hashtag::class);
    }
}
