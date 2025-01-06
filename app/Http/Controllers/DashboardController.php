<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock']);

        $view = $this->retentionRevenuePerformanceAnalysis();

        return view('pages/dashboards.index', compact('view'));
    }

    public function retentionRevenuePerformanceAnalysis()
    {
        $month_from = request('month_from');
        $month_to = request('month_to');
        $monthsArray = [];

        if ($month_from && $month_to) {
            $currentDate = Carbon::create($month_from);
            $firstMonth = $currentDate->copy()->format('Y-m');
            $currentListMonth = Carbon::create(request('month_to'));
            $listMonth = $currentListMonth->copy()->format('Y-m');
            $monthsDifference = $currentDate->diffInMonths($currentListMonth);
            $count_months = (int)$monthsDifference;

            for ($i = $count_months; $i > 0; $i--) {
                $monthsArray[$currentListMonth->copy()->subMonths($i)->format('Y-m')] = $currentListMonth->copy()->subMonths($i)->format('M y');
            }

            $monthsArray[$currentListMonth->format('Y-m')] = $currentListMonth->format('M y');

        } else {
            $currentDate = Carbon::now();
            $listMonth = $currentDate->copy()->format('Y-m');

            for ($i = 6; $i > 0; $i--) {
                if ($i === 6)
                    $firstMonth = $currentDate->copy()->subMonths($i)->format('Y-m');
                $monthsArray[$currentDate->copy()->subMonths($i)->format('Y-m')] = $currentDate->copy()->subMonths($i)->format('M y');
            }

            $monthsArray[$currentDate->format('Y-m')] = $currentDate->format('M y');
        }

        $data = DB::connection('pgsql')
            ->table('res_partner')
            ->select(
                'res_partner.id',
                'res_partner.name',
                'res_partner.username',
                DB::raw('SUM(account_move.amount_total) as total_amount'),
                DB::raw("TO_CHAR(account_move.create_date, 'YYYY-MM') as data"),
                DB::raw('count(account_move.id) as count_move')
            )
            ->leftJoin('account_move', 'res_partner.id', '=', 'account_move.partner_id')
            ->whereNotNull('res_partner.username')
            ->whereBetween(DB::raw("TO_CHAR(account_move.create_date, 'YYYY-MM')"), [$firstMonth, $listMonth])
            ->where([
                'account_move.move_type' => 'out_invoice',
                'account_move.state' => 'posted',
                'res_partner.active' => true
            ])
            ->groupBy('res_partner.id', DB::raw("TO_CHAR(account_move.create_date, 'YYYY-MM')"))
            ->orderBy('name')
            ->orderBy('data')
            ->get();


        $data = collect($data)->groupBy('name');

        return view('partials.widgets.tables._widget-16', compact('data', 'monthsArray', 'firstMonth', 'listMonth'));

    }
}
