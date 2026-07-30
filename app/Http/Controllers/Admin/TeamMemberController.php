<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamMemberController extends BaseCrudController
{
    protected string $model = TeamMember::class;

    protected string $viewPath = 'admin.team-members';

    protected string $routeName = 'team-members';

    protected string $label = 'Team Member';

    protected string $labelPlural = 'Leadership & Team';

    protected ?string $subtitle = 'Public profiles for the About and Leadership pages. These are not sign-in accounts — create those under Users.';

    protected array $searchable = ['name', 'position', 'department'];

    protected array $indexWith = ['division:id,name'];

    protected array $imageFields = ['photo' => 'team'];

    protected array $richTextFields = ['bio'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('team_members', 'slug')->ignore($model?->id)],
            'position' => ['required', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:120'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'short_bio' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:3072'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_leadership' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query->when(
            $request->query('group') === 'leadership',
            fn (Builder $q) => $q->where('is_leadership', true)
        )->when(
            $request->query('group') === 'staff',
            fn (Builder $q) => $q->where('is_leadership', false)
        );
    }

    protected function filters(): array
    {
        return [[
            'name' => 'group',
            'placeholder' => 'Everyone',
            'options' => ['leadership' => 'Leadership only', 'staff' => 'Functional leads only'],
        ]];
    }

    protected function columns(): array
    {
        return [
            'photo' => ['label' => '', 'type' => 'image', 'url' => 'photo_url'],
            'name' => ['label' => 'Name', 'type' => 'title', 'sub' => 'position'],
            'division.name' => ['label' => 'Division'],
            'is_leadership' => ['label' => 'Leadership', 'type' => 'boolean'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Full name', 'required' => true, 'col' => 8, 'id' => 'member-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'position' => ['label' => 'Job title', 'required' => true, 'col' => 6],
            'department' => ['label' => 'Department', 'col' => 6],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#member-name'],
            'email' => ['label' => 'Public email', 'input' => 'email', 'col' => 6,
                'help' => 'Optional. Shown as a contact link on the leadership page.'],
            'linkedin_url' => ['label' => 'LinkedIn', 'input' => 'url', 'col' => 6],
            'twitter_url' => ['label' => 'X (Twitter)', 'input' => 'url', 'col' => 6],
            'short_bio' => ['type' => 'textarea', 'label' => 'Short biography', 'rows' => 3,
                'help' => 'One or two sentences for the profile card.'],
            'bio' => ['type' => 'editor', 'label' => 'Full biography'],

            'photo' => ['type' => 'image', 'label' => 'Photograph', 'panel' => 'side', 'preview' => 'photo_url',
                'help' => 'Square works best — photos are shown as circles.'],
            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Group-level'],
            'phone' => ['label' => 'Phone', 'panel' => 'side'],
            'is_leadership' => ['type' => 'checkbox', 'label' => 'Group leadership', 'panel' => 'side',
                'help' => 'Leadership profiles appear on the About page and at the top of the Leadership page.'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
