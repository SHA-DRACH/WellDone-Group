<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaUploader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Shared CRUD behaviour for the content modules.
 *
 * Roughly twenty admin modules are structurally identical — list, create,
 * edit, delete, with an image or two and a sort order. Rather than repeat
 * that in twenty controllers, each subclass declares what makes it
 * different (model, views, rules, media fields) and overrides only the
 * methods where its behaviour genuinely diverges.
 *
 * Authorisation is enforced per action through the model's policy, so a
 * subclass never has to remember to call authorize().
 */
abstract class BaseCrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Blade namespace, e.g. `admin.divisions` → admin.divisions.index */
    protected string $viewPath;

    /** Route name inside the admin group, e.g. `divisions` */
    protected string $routeName;

    /** Singular label used in flash messages and form headings. */
    protected string $label = 'Record';

    /** Plural heading for the list screen. */
    protected string $labelPlural = 'Records';

    /** Optional one-line explanation shown under the page heading. */
    protected ?string $subtitle = null;

    /** Columns matched by the list screen's search box. */
    protected array $searchable = ['name'];

    /** Relations eager-loaded on the index screen. */
    protected array $indexWith = [];

    /** Relations counted on the index screen. */
    protected array $indexWithCount = [];

    /**
     * Image fields handled automatically: field name => storage directory.
     * A matching `remove_<field>` checkbox clears the existing file.
     */
    protected array $imageFields = [];

    /** Document fields handled automatically: field name => directory. */
    protected array $documentFields = [];

    /** Rich-text fields sanitised through HTMLPurifier before saving. */
    protected array $richTextFields = [];

    protected int $perPage = 20;

    public function __construct(protected MediaUploader $uploader) {}

    // ------------------------------------------------------------------
    // Subclass hooks
    // ------------------------------------------------------------------

    /** Validation rules. $model is null on create, the record on update. */
    abstract protected function rules(Request $request, ?Model $model = null): array;

    /**
     * Column definitions for the generic list screen.
     * Ignored by modules that supply their own index view.
     */
    protected function columns(): array
    {
        return ['name' => ['label' => 'Name', 'type' => 'title']];
    }

    /**
     * Field definitions for the generic create/edit screen.
     * Ignored by modules that supply their own form views.
     */
    protected function fields(?Model $record = null): array
    {
        return [];
    }

    /** Filter dropdowns rendered above the generic list. */
    protected function filters(): array
    {
        return [];
    }

    /** Extra data shared by the create and edit forms (select options etc). */
    protected function formData(?Model $model = null): array
    {
        return [];
    }

    /** Extra data for the index screen (filter options etc). */
    protected function indexData(Request $request): array
    {
        return [];
    }

    /** Apply module-specific filters to the index query. */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query;
    }

    /** Default ordering for the index screen. */
    protected function applyOrder(Builder $query): Builder
    {
        return method_exists($this->model, 'scopeOrdered')
            ? $query->ordered()
            : $query->latest('id');
    }

    /** Runs after a record is created or updated (sync relations, etc). */
    protected function afterSave(Model $model, Request $request, bool $created): void {}

    /** Runs before a record is deleted. */
    protected function beforeDelete(Model $model): void {}

    // ------------------------------------------------------------------
    // Resource actions
    // ------------------------------------------------------------------

    public function index(Request $request): View
    {
        $this->authorize('viewAny', $this->model);

        $query = $this->model::query()
            ->with($this->indexWith)
            ->withCount($this->indexWithCount);

        if ($term = trim((string) $request->query('q'))) {
            $query->where(function (Builder $inner) use ($term) {
                foreach ($this->searchable as $index => $column) {
                    $inner->{$index === 0 ? 'where' : 'orWhere'}($column, 'like', '%'.$term.'%');
                }
            });
        }

        $query = $this->applyOrder($this->applyFilters($query, $request));

        $records = $query->paginate($this->perPage)->withQueryString();

        return $this->render('index', array_merge([
            'records' => $records,
            'search' => $term ?? '',
            'columns' => $this->columns(),
            'filters' => $this->filters(),
        ], $this->indexData($request)));
    }

    public function create(): View
    {
        $this->authorize('create', $this->model);

        $record = new $this->model;

        return $this->render('create', array_merge([
            'record' => $record,
            'fields' => $this->fields(null),
        ], $this->formData(null)));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', $this->model);

        $data = $request->validate($this->rules($request, null));
        $data = $this->prepareData($data, $request, null);

        /** @var Model $record */
        $record = $this->model::query()->create($data);

        $this->afterSave($record, $request, true);

        return redirect()
            ->route("admin.{$this->routeName}.index")
            ->with('success', $this->label.' created successfully.');
    }

    public function show(string|int $id): RedirectResponse
    {
        // Most modules have no separate detail screen — editing is the detail
        // view. Modules that need one override this.
        return redirect()->route("admin.{$this->routeName}.edit", $this->findForEdit($id));
    }

    public function edit(Request $request, string|int $id): View
    {
        $record = $this->findForEdit($id);

        $this->authorize('update', $record);

        return $this->render('edit', array_merge([
            'record' => $record,
            'fields' => $this->fields($record),
        ], $this->formData($record)));
    }

    public function update(Request $request, string|int $id): RedirectResponse
    {
        $record = $this->findForEdit($id);

        $this->authorize('update', $record);

        $data = $request->validate($this->rules($request, $record));
        $data = $this->prepareData($data, $request, $record);

        $record->update($data);

        $this->afterSave($record, $request, false);

        return redirect()
            ->route("admin.{$this->routeName}.index")
            ->with('success', $this->label.' updated successfully.');
    }

    public function destroy(string|int $id): RedirectResponse
    {
        $record = $this->findForEdit($id);

        $this->authorize('delete', $record);

        $this->beforeDelete($record);

        // Soft-deleting models keep their media; hard deletes clean up.
        if (! method_exists($record, 'trashed')) {
            foreach (array_keys($this->imageFields) as $field) {
                $this->uploader->deleteFrom('public', $record->{$field});
            }
        }

        $record->delete();

        return redirect()
            ->route("admin.{$this->routeName}.index")
            ->with('success', $this->label.' deleted.');
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Resolve the record from the URL segment.
     *
     * These models expose a slug as their route key, so `route(..., $record)`
     * puts the slug in the URL — looking it up by primary key would 404 on
     * every edit. Resolve by whatever the model declares as its route key,
     * which still works for models keyed by id.
     */
    protected function findForEdit(string|int $id): Model
    {
        $instance = new $this->model;
        $routeKey = $instance->getRouteKeyName();

        $query = $this->model::query();

        // Numeric ids stay usable even on slugged models, so links built by
        // hand or held in an old bookmark keep working.
        if ($routeKey !== $instance->getKeyName() && is_numeric($id)) {
            return $query->where($instance->getKeyName(), $id)->firstOrFail();
        }

        return $query->where($routeKey, $id)->firstOrFail();
    }

    /**
     * Render a module view, falling back to the generic screen when the
     * module has not supplied its own. `create` and `edit` share one generic
     * form, since the only difference is whether the record exists.
     */
    protected function render(string $screen, array $data): View
    {
        $specific = "{$this->viewPath}.{$screen}";

        $fallback = in_array($screen, ['create', 'edit'], true)
            ? 'admin.generic.form'
            : 'admin.generic.'.$screen;

        return view(view()->exists($specific) ? $specific : $fallback, array_merge([
            'routeName' => $this->routeName,
            'modelClass' => $this->model,
            'moduleTitle' => $this->labelPlural,
            'moduleSingular' => $this->label,
            'moduleSubtitle' => $this->subtitle,
        ], $data));
    }

    /**
     * Handle uploads, removals, rich-text sanitising and checkbox casting in
     * one place so every module gets the same treatment.
     */
    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        foreach ($this->imageFields as $field => $directory) {
            unset($data[$field]);

            if ($request->boolean('remove_'.$field) && $record) {
                $this->uploader->deleteFrom('public', $record->{$field});
                $data[$field] = null;
            }

            if ($request->hasFile($field)) {
                $data[$field] = $this->uploader->image(
                    $request->file($field),
                    $directory,
                    replacing: $record?->{$field}
                );
            }
        }

        foreach ($this->documentFields as $field => $directory) {
            unset($data[$field]);

            if ($request->boolean('remove_'.$field) && $record) {
                $this->uploader->deleteFrom('public', $record->{$field});
                $data[$field] = null;
            }

            if ($request->hasFile($field)) {
                $data[$field] = $this->uploader->document(
                    $request->file($field),
                    $directory,
                    replacing: $record?->{$field}
                );
            }
        }

        /*
         * Rich text is the one place an editor can inject markup, so it is
         * always passed through HTMLPurifier — even though only trusted staff
         * reach these forms. Defence in depth against a compromised account.
         */
        foreach ($this->richTextFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = filled($data[$field]) ? clean($data[$field]) : null;
            }
        }

        return $data;
    }

    /** Turn a "one per line" textarea into an array for a JSON column. */
    protected function linesToArray(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /** Turn a "Key: value" textarea into an associative array. */
    protected function linesToPairs(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        $pairs = [];

        foreach (preg_split('/\r\n|\r|\n/', $value) as $line) {
            if (! Str::contains($line, ':')) {
                continue;
            }

            [$key, $val] = explode(':', $line, 2);
            $key = trim($key);

            if ($key !== '') {
                $pairs[$key] = trim($val);
            }
        }

        return $pairs ?: null;
    }
}
