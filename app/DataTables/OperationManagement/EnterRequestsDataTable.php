<?php

namespace App\DataTables\OperationManagement;


use App\Models\EnterRequest;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EnterRequestsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (EnterRequest $model) {
                $resource = 'locations';
                $name = $model->bound_number;
                return view('pages.apps.operation-management.enter-requests.columns._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(EnterRequest $model): QueryBuilder
    {
        return $model->select("enter_requests.*", 'enter_request_statuses.name as status_name', 'customers.name as customer_name')
            ->leftJoin('enter_request_statuses', 'enter_request_statuses.id', '=', 'enter_requests.status_id')
            ->leftJoin('customers', 'customers.id', '=', 'enter_requests.customer_id')
            ->when(Arr::get(request('order'), '0.column') == 0, function ($q) {
                return $q->latest();
            })->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('enter_requests_table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt' . "<'row'<'col-sm-12'tr>><'d-flex justify-content-between'<'col-sm-12 col-md-5'i><'d-flex justify-content-between'p>>")
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->pageLength(30);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->name('id')->title('#')->addClass('text-center'),
            Column::make('bound_number')->title('Bound Number')->addClass('text-center'),
            Column::make('customer_name')->name('customers.name')->title('Customer Name')->addClass('text-center'),
            Column::make('customs_entry_center')->title('Custom Entry Center')->addClass('text-center'),
            Column::make('gross_weight')->title('Gross weight')->addClass('text-center'),
            Column::make('cpm_result')->title('CPM')->addClass('text-center'),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible(Auth::user()->canany(['edit_enter_requests', 'delete_enter_requests']))
        ];
    }
}
