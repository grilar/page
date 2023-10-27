<?php

namespace Grilar\Page\Tables;

use Grilar\Base\Enums\BaseStatusEnum;
use Grilar\Page\Models\Page;
use Grilar\Table\Abstracts\TableAbstract;
use Grilar\Table\Actions\DeleteAction;
use Grilar\Table\Actions\EditAction;
use Grilar\Table\BulkActions\DeleteBulkAction;
use Grilar\Table\Columns\Column;
use Grilar\Table\Columns\CreatedAtColumn;
use Grilar\Table\Columns\IdColumn;
use Grilar\Table\Columns\NameColumn;
use Grilar\Table\Columns\StatusColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PageTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Page::class)
            ->addActions([
                EditAction::make()->route('pages.edit'),
                DeleteAction::make()->route('pages.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                NameColumn::make()->route('pages.edit'),
                Column::make('template')
                    ->title(trans('core/base::tables.template'))
                    ->alignLeft(),
                CreatedAtColumn::make(),
                StatusColumn::make(),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('pages.destroy'),
            ])
            ->addBulkChanges([
                'name' => [
                    'title' => trans('core/base::tables.name'),
                    'type' => 'text',
                    'validate' => 'required|max:120',
                ],
                'status' => [
                    'title' => trans('core/base::tables.status'),
                    'type' => 'customSelect',
                    'choices' => BaseStatusEnum::labels(),
                    'validate' => 'required|' . Rule::in(BaseStatusEnum::values()),
                ],
                'template' => [
                    'title' => trans('core/base::tables.template'),
                    'type' => 'customSelect',
                    'choices' => get_page_templates(),
                    'validate' => 'required',
                ],
                'created_at' => [
                    'title' => trans('core/base::tables.created_at'),
                    'type' => 'datePicker',
                ],
            ])
            ->queryUsing(function (Builder $query) {
                $query->select([
                    'id',
                    'name',
                    'template',
                    'created_at',
                    'status',
                ]);
            })
            ->onAjax(function (): JsonResponse {
                return $this->toJson(
                    $this
                        ->table
                        ->eloquent($this->query())
                        ->editColumn('template', function (Page $item) {
                            static $pageTemplates;

                            $pageTemplates ??= get_page_templates();

                            return Arr::get($pageTemplates, $item->template ?: 'default');
                        })
                );
            });
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('pages.create'), 'pages.create');
    }
}
