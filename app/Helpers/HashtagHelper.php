<?php

namespace App\Helpers;

use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use App\Models\Setting;

trait HashtagHelper
{
    /**
     * Получить все хэштеги из базы данных.
     *
     * @return string
     */
    public function getAllHashtags(): string
    {
        $hashtags = Hashtag::all();
        $hashtagList = [];

        foreach ($hashtags as $hashtag) {
            $hashtagList[] = $hashtag->hashtag;
        }

        return implode(', ', $hashtagList);
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

        if (!empty($attachedHashtags)) {
            return implode(', ', $attachedHashtags);
        }

        return 'Нет подключённых хэштегов';
    }
}