<?php

namespace App\Http\Controllers;

use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use App\Models\Outbound;
use App\Models\OutboundStatus;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {

        if (Auth::user()->hasRole('customer')) {
            $enterRequestCount = EnterRequest::where('customer_id', Auth::user()->customer?->id)
                ->where('status_id', EnterRequestStatus::APPROVED)
                ->count();

            $outboundCount = Outbound::where('customer_id', Auth::user()->customer?->id)
                ->where('enter_requests.customer_id', Auth::user()->customer?->id)
                ->where('outbounds.status_id', OutboundStatus::APPROVED)
                ->leftJoin('enter_requests', 'enter_requests.id', '=', 'outbounds.enter_request_id')
                ->count();

            return view('pages.dashboards.index', compact('enterRequestCount', 'outboundCount'));
        }

        $warehouses = Warehouse::with(['locations.lines'])->get();

        return view('pages.dashboards.index', compact('warehouses'));
    }
}
