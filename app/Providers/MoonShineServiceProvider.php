<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\SettingsResource;
use App\MoonShine\Resources\HashtagsResource;
use App\MoonShine\Resources\HashtagSettingResource;
use App\MoonShine\Resources\ChatResource;
use App\MoonShine\Resources\ReportResource;
use App\MoonShine\Resources\ReportsResource;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  MoonShine  $core
     * @param  MoonShineConfigurator  $config
     *
     */
    public function boot(CoreContract $core, ConfiguratorContract $config): void
    {
        $config->authEnable();

        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                SettingsResource::class,
                HashtagsResource::class,
                ChatResource::class,
                ReportResource::class,
            ])
            ->pages([
                ...$config->getPages(),
            ])
        ;
    }
}
