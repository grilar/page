<?php

namespace Grilar\Page\Providers;

use Grilar\Base\Facades\DashboardMenu;
use Grilar\Base\Supports\ServiceProvider;
use Grilar\Base\Traits\LoadAndPublishDataTrait;
use Grilar\Page\Models\Page;
use Grilar\Page\Repositories\Eloquent\PageRepository;
use Grilar\Page\Repositories\Interfaces\PageInterface;
use Grilar\Shortcode\View\View;
use Grilar\Theme\Facades\AdminBar;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * @since 02/07/2016 09:50 AM
 */
class PageServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->setNamespace('packages/page')
            ->loadHelpers();
    }

    public function boot(): void
    {
        $this->app->bind(PageInterface::class, function () {
            return new PageRepository(new Page());
        });

        $this
            ->loadAndPublishConfigurations(['permissions', 'general'])
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadRoutes()
            ->loadMigrations();

        $this->app['events']->listen(RouteMatched::class, function () {
            DashboardMenu::registerItem([
                'id' => 'cms-core-page',
                'priority' => 2,
                'parent_id' => null,
                'name' => 'packages/page::pages.menu_name',
                'icon' => 'fa fa-book',
                'url' => route('pages.index'),
                'permissions' => ['pages.index'],
            ]);

            if (function_exists('admin_bar')) {
                AdminBar::registerLink(
                    trans('packages/page::pages.menu_name'),
                    route('pages.create'),
                    'add-new',
                    'pages.create'
                );
            }
        });

        if (function_exists('shortcode')) {
            ViewFacade::composer(['packages/page::themes.page'], function (View $view) {
                $view->withShortcodes();
            });
        }

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);
        });

        $this->app->register(EventServiceProvider::class);
    }
}
