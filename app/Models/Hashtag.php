<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hashtag extends Model
{
    use HasFactory;

    protected $table = 'hashtags';

    protected $fillable = [
        'id',
        'hashtag',
        'report_title',
    ];

    public function reportDetails()
    {
        return $this->hasMany(Report::class);
    }

    public function Setting_Hashtag()
    {
        return $this->hasMany(Setting_Hashtag::class);
    }
}
