<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MediaUploader;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The tabbed website-settings screen.
 *
 * Rows are defined by SettingsSeeder, not created here — the form renders
 * whatever exists, using each row's `type` to choose the control. That means
 * adding a new configurable value is a seeder change, with no view to edit.
 */
class SettingController extends Controller
{
    public function __construct(
        protected MediaUploader $uploader,
        protected Settings $settings,
    ) {}

    public function edit(?string $group = null): View
    {
        abort_unless(auth()->user()->can('settings.view'), 403);

        $groups = Setting::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $group = $group && $groups->has($group) ? $group : $groups->keys()->first();

        return view('admin.settings', [
            'groups' => $groups,
            'activeGroup' => $group,
            'settings' => $groups[$group] ?? collect(),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        abort_unless(auth()->user()->can('settings.update'), 403);

        $rows = Setting::query()->where('group', $group)->get();

        foreach ($rows as $row) {
            $field = $row->key;

            match ($row->type) {
                'image' => $this->storeFile($request, $row, 'settings/images', true),
                'file' => $this->storeFile($request, $row, 'settings/files', false),
                'boolean' => $row->update(['value' => $request->boolean($field) ? '1' : '0']),
                'json' => $row->update(['value' => $this->normaliseJson($request->input($field), $row->value)]),
                default => $request->exists($field)
                    ? $row->update(['value' => $request->input($field)])
                    : null,
            };
        }

        $this->settings->flush();

        return redirect()
            ->route('admin.settings.edit', $group)
            ->with('success', 'Settings saved.');
    }

    /** Handle an uploaded image or document for a settings row. */
    protected function storeFile(Request $request, Setting $row, string $directory, bool $isImage): void
    {
        $field = $row->key;

        if ($request->boolean('remove_'.$field)) {
            $this->uploader->deleteFrom('public', $row->value);
            $row->update(['value' => null]);

            return;
        }

        if (! $request->hasFile($field)) {
            return;
        }

        $request->validate([
            $field => $isImage ? ['image', 'max:4096'] : ['file', 'max:20480'],
        ]);

        $path = $isImage
            ? $this->uploader->image($request->file($field), $directory, 1600)
            : $this->uploader->document($request->file($field), $directory);

        // Only delete the previous file if it was an upload, not a path to an
        // asset shipped with the application.
        if ($row->value && ! str_starts_with($row->value, '/')) {
            $this->uploader->deleteFrom('public', $row->value);
        }

        $row->update(['value' => $path]);
    }

    /** Keep JSON settings valid — reject malformed input rather than storing it. */
    protected function normaliseJson(?string $input, ?string $fallback): ?string
    {
        if (blank($input)) {
            return null;
        }

        json_decode($input, true);

        return json_last_error() === JSON_ERROR_NONE ? $input : $fallback;
    }
}
