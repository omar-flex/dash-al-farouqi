<?php

namespace App\DataTables\OperationManagement;


use App\Actions\GetThemeType;
use App\Models\Outbound;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class OutboundsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['status_name', 'bound_number'])
            ->editColumn('created_at', function (Outbound $model) {
                return $model->created_at->format('d M Y, h:i a');
            })
            ->editColumn('net_weight', content: function (Outbound $model) {
                return number_format($model->net_weight, '2');
            })
            ->editColumn('bound_number', content: function (Outbound $model) {
                return '<a href="">' . $model->bound_number . '</a>';
            })
            ->editColumn('status_name', content: function (Outbound $model) {
                $class = app(GetThemeType::class)->handle('bg-light-? text-?', $model->status_name);
                return '<div class="badge ' . $class . ' fw-bold">' . $model->status_name . '</div>';
            })->addColumn('action', function (Outbound $model) {
                $resource = 'outbounds';
                $name = $model->bound_number;
                return view('pages.apps.operation-management.outbounds.columns._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Outbound $model): QueryBuilder
    {
        return $model->select("outbounds.*", 'enter_requests.bound_number','enter_request_statuses.name as status_name', 'customers.name as customer_name')
            ->leftJoin('enter_request_statuses', 'enter_request_statuses.id', '=', 'outbounds.status_id')
            ->leftJoin('enter_requests', 'enter_requests.id', '=', 'outbounds.enter_request_id')
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
            ->setTableId('outbounds_table')
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
            Column::make('outbound_number')->title('Out Bound Number')->addClass('text-center text-dark'),
            Column::make('bound_number')->name('enter_requests.bound_number')->title('Bound Number')->addClass('text-center text-dark'),
            Column::make('customer_name')->name('customers.name')->title('Customer Name')->addClass('text-center'),
            Column::make('net_weight')->title('Net weight')->addClass('text-center'),
            Column::make('cpm_result')->title('CPM')->addClass('text-center'),
            Column::make('status_name')->title('Stage')->name('enter_request_statuses.name')->addClass('text-center'),
            Column::make('created_at')->title('Created At')->addClass('text-nowrap'),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible(Auth::user()->canany(['edit_enter_requests', 'delete_enter_requests']))
        ];
    }
}
