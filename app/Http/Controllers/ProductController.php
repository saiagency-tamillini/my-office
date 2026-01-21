<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ManualBillItem;
use App\Models\PartySale;


class ProductController extends Controller
{
     public function index()
    {
        $products = Product::all();
        return view('products.index', compact('products'));
    }

    // Show create form
    public function create()
    {
        return view('products.create');
    }

    // Store product
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:products,name'
        ]);

        Product::create($request->all());

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    // Show edit form
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    // Update product
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|unique:products,name,' . $product->id
        ]);

        $product->update($request->all());

        return redirect()->route('products.index')
                         ->with('success', 'Product updated successfully.');
    }

    // Delete product
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
                         ->with('success', 'Product deleted successfully.');
    }
    
    public function manual_stock_report(Request $request){
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        $items = collect();
        $filtersApplied = false;

        if ($startDate && $endDate) {
            $filtersApplied = true;

            $items = ManualBillItem::with(['product', 'partySale'])
                ->whereHas('partySale', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('bill_date', [$startDate, $endDate]);
                })
                ->orderBy(
                    PartySale::select('bill_date')
                        ->whereColumn('party_sales.id', 'manual_bill_items.party_sale_id')
                )
                ->get();
        }

        return view('pages.manual_bill_report', compact(
            'items',
            'startDate',
            'endDate',
            'filtersApplied'
        ));
    }
}
