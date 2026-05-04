<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Beat;
use App\Models\Customer;
use App\Mail\SalesReportMail;
use App\Services\SalesReportService;

class MailReportController extends Controller
{
    protected $salesReportService;

    public function __construct(SalesReportService $salesReportService)
    {
        $this->salesReportService = $salesReportService;
    }
    public function index()
    {
        $salesmen = Beat::select('salesman')->distinct()->pluck('salesman');
        $beats = Beat::orderBy('name')->get();

        return view('pages.sales_mail', compact('salesmen', 'beats'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'salesmen' => 'required|array',
            'beat_ids' => 'required|array',
            // 'from_date' => 'required',
        ]);
        try {
            $query = $this->salesReportService->buildReportQuery($request);
            $query->orderBy('customer_name', 'asc');
            $sales = $query->get();
            $salesman = $request->salesmen[0];
            $email = config('salesman_mail.' . $salesman);
            // dd($email);
            if (!$email) {
                return back()->with('error', 'Email not configured for salesman');
            }
            Mail::to($email)->send(
                new SalesReportMail($sales)
            );

            return back()->with('success', 'Mail sent successfully');
        
        } catch (\Exception $e) {
            return back()->with('error', 'Mail sending failed: ' . $e->getMessage());
        }
    }
}
