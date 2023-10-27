<?php

namespace Grilar\Page\Http\Controllers;

use Grilar\Base\Events\BeforeUpdateContentEvent;
use Grilar\Base\Events\CreatedContentEvent;
use Grilar\Base\Events\DeletedContentEvent;
use Grilar\Base\Events\UpdatedContentEvent;
use Grilar\Base\Facades\PageTitle;
use Grilar\Base\Forms\FormBuilder;
use Grilar\Base\Http\Controllers\BaseController;
use Grilar\Base\Http\Responses\BaseHttpResponse;
use Grilar\Page\Forms\PageForm;
use Grilar\Page\Http\Requests\PageRequest;
use Grilar\Page\Models\Page;
use Grilar\Page\Tables\PageTable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends BaseController
{
    public function index(PageTable $dataTable)
    {
        PageTitle::setTitle(trans('packages/page::pages.menu_name'));

        return $dataTable->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('packages/page::pages.create'));

        return $formBuilder->create(PageForm::class)->renderForm();
    }

    public function store(PageRequest $request, BaseHttpResponse $response)
    {
        $page = Page::query()->create(array_merge($request->input(), [
            'user_id' => Auth::id(),
        ]));

        event(new CreatedContentEvent(PAGE_MODULE_SCREEN_NAME, $request, $page));

        return $response->setPreviousUrl(route('pages.index'))
            ->setNextUrl(route('pages.edit', $page->getKey()))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(Page $page, FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('core/base::forms.edit_item', ['name' => $page->name]));

        return $formBuilder->create(PageForm::class, ['model' => $page])->renderForm();
    }

    public function update(Page $page, PageRequest $request, BaseHttpResponse $response)
    {
        event(new BeforeUpdateContentEvent($request, $page));

        $page->fill($request->input());
        $page->save();

        event(new UpdatedContentEvent(PAGE_MODULE_SCREEN_NAME, $request, $page));

        return $response
            ->setPreviousUrl(route('pages.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(Page $page, Request $request, BaseHttpResponse $response)
    {
        try {
            $page->delete();

            event(new DeletedContentEvent(PAGE_MODULE_SCREEN_NAME, $request, $page));

            return $response->setMessage(trans('packages/page::pages.deleted'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
