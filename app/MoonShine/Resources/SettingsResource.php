<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Setting;
use App\Models\Hashtag;
use App\Models\Setting_Hashtag;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Buttons\CreateButton;
use App\Enums\DayOfWeekEnums;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Fields\Select;
use Illuminate\Http\Request;

/**
 * @extends ModelResource<Setting>
 */
class SettingsResource extends ModelResource
{
    protected string $model = Setting::class;

    protected string $title = 'Настройки';
    protected array $with = ['Setting_Hashtag'];

    protected bool $simplePaginate = true;
    protected bool $columnSelection = true;
    protected bool $createInModal = true;
    protected bool $detailInModal = true;
    protected bool $editInModal = true;
    protected bool $cursorPaginate = true;

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('День отчета', 'report_day'),
            Text::make('Время отчета', 'report_time'),
            Number::make('Недель в периоде', 'weeks_in_period'),
            Date::make('Дата окончания текущего периода', 'current_period_end_date'),
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::MASS_DELETE)
            ->except(Action::DELETE)
            ->except(Action::CREATE);
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function formFields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Select::make('День недели', 'report_day')
                    ->options(DayOfWeekEnums::getAllDaysAdmin())
                    ->required()
                    ->searchable(),
                BelongsToMany::make('Хэштеги', 'hashtags','hashtag', resource: HashtagsResource::class)
                    ->nullable()
                    ->selectMode()
                    ->searchable()
                    ->badge(),
                Text::make('Время отчета', 'report_time')->required(),
                Number::make('Недель в периоде', 'weeks_in_period')->required(),
            ])
        ];
    }

    protected function detailFields(): iterable
    {
        $hashtags = $this->getAttachedHashtags();
        return [
            ID::make(),
            Text::make('День отчета', 'report_day'),
            Text::make('Время отчета', 'report_time'),
            Number::make('Недель в периоде', 'weeks_in_period'),
            Date::make('Дата окончания текущего периода', 'current_period_end_date'),
            BelongsToMany::make('Хэштеги', 'hashtags','hashtag', resource: HashtagsResource::class)
        ];
    }

    protected function getAttachedHashtags(): array
    {
        $settingId = $this->getItemID();
        return Setting_Hashtag::where('setting_id', $settingId)
            ->with('hashtag')
            ->get()
            ->pluck('hashtag.hashtag')
            ->toArray();
    }
    /**
     * @return list<FieldContract>
     */


    /**
     * @param Setting $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [
            'report_day' => [
                'required',
                'string',
                'in:понедельник,вторник,среда,четверг,пятница,суббота,воскресенье',
            ],
            'report_time' => [
                'required',
                'string',
                'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0][0]$/',
            ],
            'weeks_in_period' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],
        ];
    }
}