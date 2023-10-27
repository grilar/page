<?php

namespace Grilar\Page\Http\Requests;

use Grilar\Base\Enums\BaseStatusEnum;
use Grilar\Page\Supports\Template;
use Grilar\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class PageRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:400',
            'content' => 'required|string',
            'template' => Rule::in(array_keys(Template::getPageTemplates())),
            'status' => Rule::in(BaseStatusEnum::values()),
        ];
    }
}
