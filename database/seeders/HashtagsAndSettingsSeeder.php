<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hashtag;
use App\Models\Setting;
use App\Models\Setting_Hashtag;
use Carbon\Carbon;

class HashtagsAndSettingsSeeder extends Seeder
{
    /**
     * Заполнение таблиц hashtags, settings и setting_hashtag тестовыми данными.
     */
    public function run()
    {
        // Отключаем проверку внешних ключей
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Очистка таблиц перед заполнением (опционально)
        \DB::table('setting_hashtags')->truncate();
        \DB::table('hashtags')->truncate();
        \DB::table('settings')->truncate();

        // Включаем проверку внешних ключей
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Заполнение таблицы hashtags
        $hashtags = [
            ['id' => 1, 'hashtag' => '#митрепорт', 'report_title' => 'митрепорт'],
            ['id' => 2, 'hashtag' => '#еженедельныйотчет','report_title' => 'еженедельныйотчет'],
        ];

        foreach ($hashtags as $hashtag) {
            Hashtag::create($hashtag);
        }

        Setting::create([
            'report_day' => 'Понедельник',
            'report_time' => '10:00',
            'weeks_in_period' => 1,
            'current_period_end_date' => Carbon::now()
                ->next(1) // Следующий понедельник
                ->setTime(9, 59, 59), // Устанавливаем время 9:59:59
        ]);

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