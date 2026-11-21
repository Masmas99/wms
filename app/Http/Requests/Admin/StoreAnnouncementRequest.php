<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAnnouncementRequest extends FormRequest
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
            'body' => [
                Rule::requiredIf(fn (): bool => $this->input('type') !== Announcement::TYPE_BANNER),
                'nullable',
                'string',
                'max:5000',
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'type' => ['required', Rule::in([
                Announcement::TYPE_INFO,
                Announcement::TYPE_SUCCESS,
                Announcement::TYPE_WARNING,
                Announcement::TYPE_DANGER,
                Announcement::TYPE_BANNER,
            ])],
            'audience' => ['required', Rule::in([
                Announcement::AUDIENCE_ALL,
                Announcement::AUDIENCE_SELECTED,
            ])],
            'user_ids' => ['exclude_unless:audience,'.Announcement::AUDIENCE_SELECTED, 'required', 'array', 'min:1'],
            'user_ids.*' => ['uuid', 'distinct', 'exists:users,id'],
        ];
    }

    /**
     * Require an image when the announcement is a banner.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== Announcement::TYPE_BANNER || $this->hasFile('image')) {
                return;
            }

            $validator->errors()->add('image', 'Gambar banner wajib diunggah untuk jenis pemberitahuan banner.');
        });
    }
}
