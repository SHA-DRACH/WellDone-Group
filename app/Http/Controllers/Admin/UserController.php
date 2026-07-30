<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use App\Notifications\StaffAccountCreated;
use App\Services\MediaUploader;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['roles:id,name', 'division:id,name'])
            ->search($request->query('q'))
            ->when($request->query('role'), fn ($q, $role) => $q->role($role))
            ->when($request->query('division'), fn ($q, $id) => $q->where('division_id', $id))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'records' => $users,
            'search' => $request->query('q', ''),
            'roles' => Role::query()->orderBy('name')->pluck('name', 'name'),
            'divisions' => Division::query()->ordered()->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', $this->formData(new User));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate($this->rules($request, null));

        $user = DB::transaction(function () use ($request, $data) {
            $user = new User;

            $user->fill(collect($data)->except(['roles', 'avatar', 'send_welcome'])->all());

            // The account is created without a usable password: the welcome
            // email carries a signed link for the user to set their own.
            $user->password = Str::random(40);
            $user->is_active = $request->boolean('is_active', true);

            if ($request->hasFile('avatar')) {
                $user->avatar = $this->uploader->image($request->file('avatar'), 'users/avatars', 400);
            }

            $user->save();
            $user->syncRoles($this->assignableRoles($request->input('roles', [])));

            return $user;
        });

        // Verification email (Laravel's MustVerifyEmail flow) plus the
        // welcome email with the set-password link.
        $user->sendEmailVerificationNotification();

        if ($request->boolean('send_welcome', true)) {
            $user->notify(new StaffAccountCreated($user->load('division')));
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Account created for {$user->name}. A welcome email and verification link have been sent to {$user->email}.");
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', array_merge(['record' => $user], $this->formData($user)));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate($this->rules($request, $user));

        DB::transaction(function () use ($request, $user, $data) {
            $user->fill(collect($data)->except(['roles', 'avatar', 'password', 'send_welcome'])->all());

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            if ($request->boolean('remove_avatar')) {
                $this->uploader->deleteFrom('public', $user->avatar);
                $user->avatar = null;
            }

            if ($request->hasFile('avatar')) {
                $user->avatar = $this->uploader->image(
                    $request->file('avatar'), 'users/avatars', 400, replacing: $user->avatar
                );
            }

            $user->is_active = $request->boolean('is_active');
            $user->save();

            if ($this->user()->can('assignRoles', $user)) {
                $user->syncRoles($this->assignableRoles($request->input('roles', [])));
            }
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Soft delete: the audit log, authored articles and assigned leads
        // all reference this row, so it is retired rather than removed.
        $user->update(['is_active' => false]);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account deactivated and archived.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "{$user->name}'s account has been reactivated."
            : "{$user->name}'s account has been deactivated.");
    }

    // ------------------------------------------------------------------

    protected function rules(Request $request, ?User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'is_active' => ['boolean'],
            'send_welcome' => ['boolean'],

            // Only set on update, and only when the field is filled in.
            'password' => $user
                ? ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
                : ['nullable'],
        ];
    }

    protected function formData(User $user): array
    {
        return [
            'record' => $user,
            'roles' => $this->selectableRoles(),
            'divisions' => Division::query()->ordered()->pluck('name', 'id'),
            'currentRoles' => $user->exists ? $user->getRoleNames()->all() : [],
        ];
    }

    /**
     * Nobody may hand out the super administrator role except a super
     * administrator — otherwise an administrator could escalate themselves.
     */
    protected function selectableRoles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->pluck('name', 'name')
            ->reject(fn ($name) => $name === Roles::SUPER_ADMIN && ! $this->user()->isSuperAdmin())
            ->all();
    }

    protected function assignableRoles(array $requested): array
    {
        $allowed = array_keys($this->selectableRoles());

        return array_values(array_intersect($requested, $allowed));
    }

    protected function user(): User
    {
        return auth()->user();
    }
}
