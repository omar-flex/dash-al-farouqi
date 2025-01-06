<?php

namespace App\DataTables\Hotel;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BlackOutDatesDataTable extends DataTable
{

    public function dataTable()
    {
        $response = Http::acceptJson()->withBody(json_encode([
            'user_id' => Auth::user()->user_id,
            'hotel_id' => $this->hotel_id,
        ]))->get('https://ashaxisdmc.odoo.com/api/tariff/get_hotel_details');

        if ($response->ok()) {
            $data = collect(Arr::get($response->json(), 'result.details.black_out_dates'));
        } else {
            $data = collect([]);
        }

        return (new CollectionDataTable($data))->addIndexColumn();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('black-out-dates-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('contract.blackOutDatesDataTable', ['hotel_id' => $this->hotel_id]))
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
            Column::make('black_out_of_dates_id'),
            Column::make('name'),
            Column::make('date_from')->title('Date From'),
            Column::make('date_to')->title('Date To'),
        ];
    }
}
