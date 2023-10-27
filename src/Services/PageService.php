<?php

namespace Grilar\Page\Services;

use Grilar\Base\Enums\BaseStatusEnum;
use Grilar\Base\Facades\BaseHelper;
use Grilar\Base\Supports\RepositoryHelper;
use Grilar\Media\Facades\RvMedia;
use Grilar\Page\Models\Page;
use Grilar\SeoHelper\Facades\SeoHelper;
use Grilar\SeoHelper\SeoOpenGraph;
use Grilar\Slug\Models\Slug;
use Grilar\Theme\Facades\Theme;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class PageService
{
    public function handleFrontRoutes(Slug|array $slug): Slug|array|Builder
    {
        if (! $slug instanceof Slug) {
            return $slug;
        }

        $condition = [
            'id' => $slug->reference_id,
            'status' => BaseStatusEnum::PUBLISHED,
        ];

        if (Auth::check() && request()->input('preview')) {
            Arr::forget($condition, 'status');
        }

        if ($slug->reference_type !== Page::class) {
            return $slug;
        }

        $page = Page::query()
            ->where($condition)
            ->with('slugable');

        $page = RepositoryHelper::applyBeforeExecuteQuery($page, new Page(), true)->first();

        if (empty($page)) {
            if ($slug->reference_id == BaseHelper::getHomepageId()) {
                return [];
            }

            abort(404);
        }

        $meta = new SeoOpenGraph();

        if ($page->image) {
            $meta->setImage(RvMedia::getImageUrl($page->image));
        }

        if (! BaseHelper::isHomepage($page->getKey())) {
            SeoHelper::setTitle($page->name)
                ->setDescription($page->description);

            $meta->setTitle($page->name);
            $meta->setDescription($page->description);
        } else {
            $siteTitle = theme_option('seo_title') ? theme_option('seo_title') : theme_option('site_title');
            $seoDescription = theme_option('seo_description');

            SeoHelper::setTitle($siteTitle)
                ->setDescription($seoDescription);

            $meta->setTitle($siteTitle);
            $meta->setDescription($seoDescription);
        }

        $meta->setUrl($page->url);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        SeoHelper::meta()->setUrl($page->url);

        if ($page->template) {
            Theme::uses(Theme::getThemeName())
                ->layout($page->template);
        }

        if (function_exists('admin_bar')) {
            admin_bar()
                ->registerLink(trans('packages/page::pages.edit_this_page'), route('pages.edit', $page->getKey()), null, 'pages.edit');
        }

        if (function_exists('shortcode')) {
            shortcode()->getCompiler()->setEditLink(route('pages.edit', $page->getKey()), 'pages.edit');
        }

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, PAGE_MODULE_SCREEN_NAME, $page);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add($page->name, $page->url);

        return [
            'view' => 'page',
            'default_view' => 'packages/page::themes.page',
            'data' => compact('page'),
            'slug' => $page->slug,
        ];
    }
}
