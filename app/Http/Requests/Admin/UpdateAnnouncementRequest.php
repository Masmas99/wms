<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in([
                Announcement::TYPE_INFO,
                Announcement::TYPE_SUCCESS,
                Announcement::TYPE_WARNING,
                Announcement::TYPE_DANGER,
            ])],
            'audience' => ['required', Rule::in([
                Announcement::AUDIENCE_ALL,
                Announcement::AUDIENCE_SELECTED,
            ])],
            'user_ids' => ['exclude_unless:audience,'.Announcement::AUDIENCE_SELECTED, 'required', 'array', 'min:1'],
            'user_ids.*' => ['uuid', 'distinct', 'exists:users,id'],
        ];
    }
}
