<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Chat;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\UI\Fields\Url;

/**
 * @extends ModelResource<Chat>
 */
class ChatResource extends ModelResource
{
    protected string $model = Chat::class;

    protected string $title = 'Чаты';
    protected bool $cursorPaginate = true;
    protected bool $detailInModal = true;
    protected bool $simplePaginate = true;
    protected int $itemsPerPage = 10;
    protected bool $columnSelection = true;

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::MASS_DELETE)
            ->except(Action::DELETE)
            ->except(Action::CREATE)
            ->except(Action::UPDATE);
    }
    
    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Name', 'name')->sortable(),
            Url::make('Ссылка на чат', 'chat_link')->sortable(),
            Text::make('Chat ID', 'chat_id')->sortable(),
        ];
    }

    protected function actionButtons():ListOf
    {
        return parent::topButtons()->add(
            ActionButton::make('Создать чат', 'create'));
    }
    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Name', 'name'),
            Url::make('Ссылка на чат', 'chat_link'),
            Text::make('Chat ID', 'chat_id'),
        ];
    }
}