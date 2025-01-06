<?php

namespace App\DataTables\Transportation;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ContractRoutesDataTable extends DataTable
{

    public function dataTable()
    {

        $response = Http::acceptJson()->withBody(json_encode([
            'user_id' => Auth::user()->user_id,
            'transportation_id' => $this->transportation_id,
        ]))->get('https://ashaxisdmc.odoo.com/api/tariff/get_transportation_details');

        if ($response->ok()) {
            $data = collect(Arr::get($response->json(), 'result.details.route_lines'));
        } else {
            $data = collect([]);
        }

        return (new CollectionDataTable($data))
            ->addColumn('vehicles_name', function ($transportation) {
                $vehicles = Arr::get($transportation, 'vehicles_id');
                return Arr::get($vehicles, 'name', '-');
            })->addIndexColumn();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('route_lines-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('contract.contractRoutesDataTable', ['transportation_id' => $this->transportation_id]))
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
            Column::make('DT_RowIndex')->name('id')->title('#')->addClass('text-center')->width(100),
            Column::make('name')->name('name')->title('Name'),
            Column::make('car_cost')->addClass('text-center'),
            Column::make('mini_van_cost')->addClass('text-center'),
        ];
    }
}
