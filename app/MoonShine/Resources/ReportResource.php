<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Report;
use App\Actions\CreateManagerClientReport;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Url;

/**
 * @extends ModelResource<Report>
 */
class ReportResource extends ModelResource
{
    protected string $model = Report::class;
    protected string $title = 'Отчёты';
    protected bool $cursorPaginate = true;
    protected bool $detailInModal = true;
    protected bool $simplePaginate = true;
    protected bool $columnSelection = true;
    protected int $itemsPerPage = 10;

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::MASS_DELETE)
            ->except(Action::DELETE)
            ->except(Action::CREATE)
            ->except(Action::UPDATE);
    }

    public function topButtons(): ListOf
    {

        return parent::topButtons()->add(
            ActionButton::make('Отчёт Менеджер-Клиент')
                ->method('managerClientReport')
        );

    }

    public function managerClientReport()
    {
        $reportAction = new CreateManagerClientReport();
        $reportAction->execute();
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Дата начала', 'start_date')->sortable(),
            Text::make('Дата окончания', 'end_date')->sortable(),
            Url::make('Ссылка на таблицу', 'sheet_url'),
            Text::make('Чат', 'chat.name')->sortable(),
            Text::make('Хэштег', 'hashtag.hashtag')->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Дата начала', 'start_date'),
            Text::make('Дата окончания', 'end_date'),
            Url::make('Ссылка на таблицу', 'sheet_url'),
            Text::make('Чат', 'chat.name'),
            Text::make('Хэштег', 'hashtag.hashtag'),
        ];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Created at'),
        ];
    }
}