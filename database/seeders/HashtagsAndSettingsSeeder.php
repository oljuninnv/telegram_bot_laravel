<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Models\Setting_Hashtag;

class HashtagsAndSettingsSeeder extends Seeder
{
    /**
     * Заполнение таблиц hashtags, settings и setting_hashtag тестовыми данными.
     */
    public function run()
    {
        // Очистка таблиц перед заполнением (опционально)
        \DB::table('hashtags')->truncate();
        \DB::table('settings')->truncate();
        \DB::table('setting_hashtags')->truncate();

        // Заполнение таблицы hashtags
        $hashtags = [
            ['id' => 1, 'hashtag' => '#митрепорт'],
            ['id' => 2, 'hashtag' => '#еженедельныйотчет'],
        ];

        foreach ($hashtags as $hashtag) {
            Hashtag::create($hashtag);
        }

        // Заполнение таблицы settings
        $settings = [
            ['id' => 1, 'report_day' => 'понедельник', 'report_time' => '10:00', 'weeks_in_period' => 1],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }

        // Заполнение таблицы setting_hashtags
        $settingHashtags = [
            ['id' => 1, 'setting_id' => 1, 'hashtag_id' => 1],
            ['id' => 2, 'setting_id' => 1, 'hashtag_id' => 2],
        ];

        foreach ($settingHashtags as $settingHashtag) {
            Setting_Hashtag::create($settingHashtag);
        }

        $this->command->info('Таблицы hashtags, settings и setting_hashtags успешно заполнены!');
    }
}