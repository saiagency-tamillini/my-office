<?php

namespace App\Http\Controllers;

use App\Models\RouteMaster;
use Illuminate\Http\Request;

class RouteMasterController extends Controller
{
    public function index()
    {
        $routes = RouteMaster::orderBy('name')->get();

        return view('routes.index', compact('routes'));
    }

    public function create()
    {
        return view('routes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:routes,name',
        ]);

        RouteMaster::create([
            'name' => $request->name,
        ]);

        return redirect()->route('routes.index')->with('success', 'Route created successfully');
    }

    public function edit(RouteMaster $route)
    {
        return view('routes.edit', compact('route'));
    }

    public function update(Request $request, RouteMaster $route)
    {
        $request->validate([
            'name' => 'required|unique:routes,name,' . $route->id,
        ]);

        $route->update([
            'name' => $request->name,
        ]);

        return redirect()->route('routes.index')->with('success', 'Route updated successfully');
    }

    public function destroy(RouteMaster $route)
    {
        $route->delete();

        return redirect()->route('routes.index')->with('success', 'Route deleted successfully');
    }
}
