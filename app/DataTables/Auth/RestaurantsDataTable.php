<?php

namespace App\DataTables\Auth;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RestaurantsDataTable extends DataTable
{

    public function dataTable()
    {
        $response = Http::acceptJson()->withBody(json_encode([
            'user_id' => Auth::user()->user_id,
        ]))->get('https://ashaxisdmc.odoo.com/api/tariff/get_restaurant');

        if ($response->ok()) {
            $data = collect(Arr::get($response->json(), 'result.data'));
        } else {
            $data = collect([]);
        }

        return (new CollectionDataTable($data))
            ->rawColumns(['type', 'partner_name', 'action'])
            ->editColumn('partner_name', function ($transportation) {
                return '<a href="' . route('contract.restaurants.show', Arr::get($transportation, 'id')) . '">' . Arr::get($transportation, 'partner_id.name') . '</a>';
            })
            ->editColumn('type', function ($transportation) {
                return sprintf('<div class="badge  ' . app(\App\Actions\GetThemeType::class)
                        ->handle('badge-?', Arr::get($transportation, 'type')) . ' fw-bold">%s</div>', Arr::get($transportation, 'type'));
            })
            ->addColumn('action', function ($transportation) {
                return view('pages.apps.contract.restaurants.columns._actions', compact('transportation'));
            })
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.


    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('transportations-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt' . "<'row'<'col-sm-12'tr>><'d-flex justify-content-between'<'col-sm-12 col-md-5'i><'d-flex justify-content-between'p>>")
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->name('id')->title('#')->addClass('text-center'),
            Column::make('partner_name')->name('partner_id.name')->title('Hotel Name'),
            Column::make('name')->name('name')->title('Custom Name'),
            Column::make('type')->name('type')->addClass('text-center'),
            Column::make('date_from')->addClass('text-center'),
            Column::make('date_to')->addClass('text-center'),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
        ];
    }
}
