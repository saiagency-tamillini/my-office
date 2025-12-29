<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Beat;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
      public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'name'); // default column
        $sortOrder = $request->get('sort_order', 'asc'); // default order

        $customers = Customer::with('beat')
            ->orderBy($sortBy, $sortOrder)
            ->get();
        return view('customers.index', compact('customers', 'sortBy', 'sortOrder'));
    }

    public function create()
    {
        $beats = Beat::all();
        return view('customers.create', compact('beats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'beat_id' => 'required|exists:beats,id',
        ]);

        Customer::create($request->all());

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $beats = Beat::all();
        return view('customers.edit', compact('customer', 'beats'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'beat_id' => 'required|exists:beats,id',
        ]);

        $customer->update($request->all());

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function transactions(Customer $customer)
    {
        $transactions = DB::table('payment_entries')
            ->where('customer_id', $customer->id)
            ->orderBy('bill_no')
            ->orderBy('created_at')
            ->get()
            ->groupBy('bill_no');

        return view('customers.transactions', compact('customer', 'transactions'));
    }
}
