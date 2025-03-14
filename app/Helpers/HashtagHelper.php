<?php

namespace App\Helpers;

use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

trait HashtagHelper
{
    /**
     * Получить все хэштеги из базы данных.
     *
     * @return string
     */
    public function getAllHashtags(): string
    {
        return Cache::remember('all_hashtags', 3600, function () {
            return Hashtag::pluck('hashtag')->implode(', ');
        });
    }

    /**
     * Получить подключённые хэштеги к текущей настройке.
     *
     * @param Setting $setting
     * @return string
     */
    public function getAttachedHashtags(Setting $setting): string
    {
        $attachedHashtags = Setting_Hashtag::where('setting_id', $setting->id)
            ->with('hashtag')
            ->get()
            ->pluck('hashtag.hashtag')
            ->toArray();

        return !empty($attachedHashtags) ? implode(', ', $attachedHashtags) : 'Нет подключённых хэштегов';
    }
}