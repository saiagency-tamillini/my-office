<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Beat;

class BeatController extends Controller
{
    // List beats
    public function index()
    {
        $beats = Beat::orderBy('name')->get();
        return view('beats.index', compact('beats'));
    }

    // Show create form
    public function create()
    {
        return view('beats.create');
    }

    // Store beat
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:beats,name',
            'salesman' => 'required',
        ]);

        Beat::create([
            'name' => $request->name,
            'salesman' => $request->salesman,
            'is_active' => $request->is_active ?? 1,
        ]);

        return redirect()->route('beats.index')->with('success', 'Beat created successfully');
    }

    // Show edit form
    public function edit(Beat $beat)
    {
        return view('beats.edit', compact('beat'));
    }

    // Update beat
    public function update(Request $request, Beat $beat)
    {
        $request->validate([
            'name' => 'required|unique:beats,name,' . $beat->id,
            'salesman' => 'required',
        ]);

        $beat->update([
            'name' => $request->name,
            'salesman' => $request->salesman,
            'is_active' => $request->is_active ?? 1,
        ]);

        return redirect()->route('beats.index')->with('success', 'Beat updated successfully');
    }

    // Delete beat
    public function destroy(Beat $beat)
    {
        $beat->delete();
        return redirect()->route('beats.index')->with('success', 'Beat deleted successfully');
    }

}
