<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Laravel\Components\Layout\{Locales, Notifications, Profile, Search};
use MoonShine\UI\Components\{Breadcrumbs,
    Components,
    Layout\Flash,
    Layout\Div,
    Layout\Body,
    Layout\Burger,
    Layout\Content,
    Layout\Footer,
    Layout\Head,
    Layout\Favicon,
    Layout\Assets,
    Layout\Meta,
    Layout\Header,
    Layout\Html,
    Layout\Layout,
    Layout\Logo,
    Layout\Menu,
    Layout\Sidebar,
    Layout\ThemeSwitcher,
    Layout\TopBar,
    Layout\Wrapper,
    When};
use App\MoonShine\Resources\SettingsResource;
use MoonShine\MenuManager\MenuItem;
use MoonShine\MenuManager\MenuGroup;
use App\MoonShine\Resources\HashtagsResource;
// use App\MoonShine\Resources\HashtagSettingResource;
use App\MoonShine\Resources\ChatResource;
use App\MoonShine\Resources\ReportResource;
use App\MoonShine\Resources\ReportsResource;

final class MoonShineLayout extends AppLayout
{
    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        return [
            ...parent::menu(),
            MenuGroup::make('Настройки',[
                MenuItem::make('Отчеты Менеджер-Клиент', SettingsResource::class),
                MenuItem::make('Хэштеги', HashtagsResource::class),
            ]),
            MenuItem::make('Чаты', ChatResource::class),
            MenuItem::make('Отчёты', ReportResource::class),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }

    public function build(): Layout
    {
        return parent::build();
    }
}
