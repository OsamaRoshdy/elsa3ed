<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $users = User::query()
            ->with('client')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('role')->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'filters' => $filters]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => User::ROLE_CLIENT, 'daftra_client_id' => $request->integer('client') ?: null]),
            'clients' => Client::orderBy('name')->get(['daftra_id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return redirect()->route('admin.users.index')->with('success', __('User created successfully.'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'clients' => Client::orderBy('name')->get(['daftra_id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', __('You cannot remove your own admin role.'));
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', __('User updated successfully.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', __('User deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::min(8), 'confirmed'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_CLIENT])],
            'daftra_client_id' => ['nullable', 'required_if:role,'.User::ROLE_CLIENT, 'integer', Rule::exists('clients', 'daftra_id')],
        ]);

        if ($data['role'] === User::ROLE_ADMIN) {
            $data['daftra_client_id'] = null;
        }

        return $data;
    }
}
