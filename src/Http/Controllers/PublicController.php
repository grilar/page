<?php

namespace Grilar\Page\Http\Controllers;

use Grilar\Page\Models\Page;
use Grilar\Page\Services\PageService;
use Grilar\Slug\Facades\SlugHelper;
use Grilar\Theme\Events\RenderingSingleEvent;
use Grilar\Theme\Facades\Theme;
use Illuminate\Routing\Controller;

class PublicController extends Controller
{
    public function getPage(string $slug, PageService $pageService)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Page::class));

        if (! $slug) {
            abort(404);
        }

        $data = $pageService->handleFrontRoutes($slug);

        if (isset($data['slug']) && $data['slug'] !== $slug->key) {
            return redirect()->to(url(SlugHelper::getPrefix(Page::class) . '/' . $data['slug']));
        }

        event(new RenderingSingleEvent($slug));

        return Theme::scope($data['view'], $data['data'], $data['default_view'])->render();
    }
}
