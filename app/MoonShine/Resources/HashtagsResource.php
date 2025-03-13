<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Hashtag;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;


/**
 * @extends ModelResource<Hashtag>
 */
class HashtagsResource extends ModelResource
{
    protected string $model = Hashtag::class;

    protected string $title = 'Хэштеги';

    protected array $with = ['Setting_Hashtag'];

    protected bool $simplePaginate = true;
    protected bool $columnSelection = true;
    protected bool $createInModal = true;
    protected bool $detailInModal = true;
    protected bool $editInModal = true;
    protected int $itemsPerPage = 10;

    protected bool $cursorPaginate = true;

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Хэштег', 'hashtag'),
            Text::make('Заголовок', 'report_title'),
        ];
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function formFields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Хэштег', 'hashtag'),
                Text::make('Заголовок', 'report_title'),
            ])
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Хэштег', 'hashtag'),
            Text::make('Заголовок', 'report_title'),
        ];
    }

    /**
     * @param Hashtag $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [
            'hashtag' => [
                'required',
                'string',
                'regex:/^#/',
            ],
            'report_title' => 'required|string',
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'hashtag',
            'report_title'
        ];
    }
}
