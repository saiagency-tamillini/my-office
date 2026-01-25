<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role; 
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest()->paginate(10);
        $roles = Role::orderBy('name')->get(); 

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:100'],
            'username' => ['required','string','max:50','unique:users,username'],
            'email' => ['nullable','email','max:150','unique:users,email'],
            'role_id' => ['required','integer', 'exists:roles,id'], 
            'password' => ['required','string','min:6','confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'role_id' => $validated['role_id'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:100'],
            'username' => [
                'required','string','max:50',
                Rule::unique('users','username')->ignore($user->id)
            ],
            'email' => [
                'nullable','email','max:150',
                Rule::unique('users','email')->ignore($user->id)
            ],
            'role_id' => ['required','integer','exists:roles,id'],
            'password' => ['nullable','string','min:6','confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'] ?? null;
        $user->role_id = $validated['role_id'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('status', 'User deleted successfully.');
    }
}
