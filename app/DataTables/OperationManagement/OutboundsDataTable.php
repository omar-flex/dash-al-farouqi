<?php

namespace App\DataTables\OperationManagement;


use App\Actions\GetThemeType;
use App\Models\Outbound;
use App\Models\OutboundStatus;
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
            ->rawColumns(['status_name', 'bound_number', 'outbound_number', 'customer_name'])
            ->filterColumn('product_names', function ($query, $keyword) {
                $query->havingRaw('GROUP_CONCAT(DISTINCT products.name) LIKE ?', ["%{$keyword}%"]);
            })
            ->editColumn('customer_name', content: function (Outbound $model) {
                return '<div class="d-flex align-items-center justify-content-center">
                    <div class="d-flex flex-column">
                    <span class="fw-bold">' . $model->customer_name . '</span>
                     <span class="text-muted">' . $model->company_name . '</span>
                   </div> </div>';
            })
            ->editColumn('product_name', function ($row) {
                return $row->product_name ?? '—';
            })
            ->editColumn('date', function (Outbound $model) {
                return $model->date->format('d/m/Y');
            })
            ->editColumn('net_weight', content: function (Outbound $model) {
                return number_format($model->net_weight, '2');
            })
            ->editColumn('gross_weight', content: function (Outbound $model) {
                return number_format($model->gross_weight, '2');
            })
            ->editColumn('total_cost', content: function (Outbound $model) {
                return number_format($model->total_cost, '2');
            })
            ->editColumn('bound_number', content: function (Outbound $model) {
                return '<a href="' . route('operation-management.enter_requests.show', $model->inbound_id) . '">' . $model->bound_number . '</a>';
            })
            ->editColumn('outbound_number', content: function (Outbound $model) {
                return '<a href="' . route('operation-management.outbounds.show', $model->id) . '">' . $model->outbound_number . '</a>';
            })
            ->editColumn('status_name', content: function (Outbound $model) {
                $class = app(GetThemeType::class)->handle('bg-light-? text-?', $model->status_name);
                return '<div class="badge ' . $class . ' fw-bold">' . $model->status_name . '</div>';
            })->addColumn('action', function (Outbound $model) {
                $resource = 'outbounds';
                $name = $model->outbound_number;
                return view('pages.apps.operation-management.outbounds.columns._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Outbound $model): QueryBuilder
    {

        return $model->selectRaw('
        outbounds.*,
        enter_requests.bound_number,
        enter_requests.id as inbound_id,
        outbound_statuses.name as status_name,
        customers.name as customer_name,
        clearance_companies.name as company_name,
        GROUP_CONCAT(DISTINCT products.name SEPARATOR ", ") as product_names
    ')->leftJoin('outbound_statuses', 'outbound_statuses.id', '=', 'outbounds.status_id')
            ->leftJoin('enter_requests', 'enter_requests.id', '=', 'outbounds.enter_request_id')
            ->leftJoin('customers', 'customers.id', '=', 'enter_requests.customer_id')
            ->leftJoin('outbound_warehouse_items', 'outbound_warehouse_items.outbound_id', '=', 'outbounds.id')
            ->leftJoin('warehouse_items', 'warehouse_items.id', '=', 'outbound_warehouse_items.warehouse_item_id')
            ->leftJoin('products', 'products.id', '=', 'warehouse_items.product_id')
            ->leftJoin('clearance_companies', 'clearance_companies.id', '=', 'enter_requests.clearance_company_id')
            ->groupBy('outbounds.id')
            ->when(Auth::user()->hasRole('customer'), function ($query) {
                return $query->where('enter_requests.customer_id', Auth::user()->customer?->id)
                    ->where('outbounds.status_id', OutboundStatus::APPROVED)
                    ->when(request()->routeIs('operation-management.enter_requests.show'), function ($query) {
                        return $query->where('outbounds.enter_request_id', request()->route('enter_request')->id);
                    });
            })
            ->when(Arr::get(request('order'), '0.column') == 0, function ($q) {
                return $q->latest();
            })->when(request('customer_id'), function ($q) {
                return $q->where('enter_requests.customer_id', request('customer_id'));
            })
            ->when(request('company_id'), function ($q) {
                return $q->where('enter_requests.clearance_company_id', request('company_id'));
            })
            ->when(request('status_id'), function ($q) {
                return $q->where('outbounds.status_id', request('status_id'));
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
            Column::make('bound_number')
                ->name('enter_requests.bound_number')
                ->title('In Bound Number')
                ->addClass('text-center text-dark')
                ->visible(!request()->routeIs('operation-management.enter_requests.show')),
            Column::make('customer_name')
                ->name('customers.name')
                ->title('Customer Name')
                ->addClass('text-center')
                ->visible(!Auth::user()->hasRole('customer')),
            Column::make('net_weight')->title('Net weight')->addClass('text-center'),
            Column::make('gross_weight')->title('Gross Weight')->addClass('text-center'),
            Column::make('total_cost')->title('Total Cost')->addClass('text-center'),
            Column::make('cpm_result')->title('CPM')->addClass('text-center'),
            Column::make('status_name')->title('Stage')
                ->name('outbound_statuses.name')
                ->addClass('text-center')
                ->visible(!Auth::user()->hasRole('customer')),
            Column::make('date')->title('Date')->addClass('text-nowrap'),
            Column::make('product_names')->name('products.name')->addClass('text-nowrap')->visible(false),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible(Auth::user()->canany(['edit_enter_requests', 'delete_enter_requests']))
        ];
    }
}
