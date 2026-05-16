<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendNoticationMail;

class AuthController extends Controller
{
     // Signup page
    public function showRegister()
    {
        return view('auth.register');
    }

    // Handle signup
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'username' => ['required','string','max:255','unique:users,username'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','confirmed'],
        ]);
        $guestRoleId = Role::where('name', 'guest')->value('id');
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $guestRoleId,
        ]);
        return redirect()->route('home'); // change to your preferred page
    }

    // Handle login (from welcome page)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required','string'], // username OR email
            'password' => ['required','string'],
        ]);

        $username = $credentials['username'];
        $password = $credentials['password'];

    //    dd(1);
        if (Auth::attempt(['username' => $username, 'password' => $password], true)) {
            $request->session()->regenerate();
            // Logged in user
            $user = Auth::user();
            // Send mail
            // Mail::to($user->email)->send(new SendNoticationMail($user));
            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
