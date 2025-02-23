<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting_Hashtag extends Model
{
    use HasFactory;

    protected $table = 'setting_hashtags';

    protected $fillable = [
        'id',
        'hashtag_id',
        'setting_id',
    ];

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }

    public function hashtag()
    {
        return $this->belongsTo(Hashtag::class);
    }
}
