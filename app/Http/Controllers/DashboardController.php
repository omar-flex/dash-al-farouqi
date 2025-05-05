<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::with(['locations.lines'])->get();

        return view('pages.dashboards.index', compact('warehouses'));
    }
}
