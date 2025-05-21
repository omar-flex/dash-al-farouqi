<?php

namespace App\DataTables\OperationManagement;


use App\Actions\GetThemeType;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ManifestAuthorizationsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['status_name', 'bound_number','outbounds_count','customer_name'])
            ->editColumn('customer_name', content: function (EnterRequest $model) {
                return '<div class="d-flex align-items-center justify-content-center">
                    <div class="d-flex flex-column">
                     <span class="text-muted">' . $model->customer_name . '</span>
                     <span class="text-muted">' . $model->company_name . '</span>
                   </div> </div>';
            })
            ->editColumn('created_at', function (EnterRequest $model) {
                return $model->created_at->format('d M Y, h:i a');
            })
            ->editColumn('bound_number', content: function (EnterRequest $model) {
                return '<a href="' . route('operation-management.enter_requests.show', $model->id) . '">' . $model->bound_number . '</a>';
            })
            ->editColumn('outbounds_count', content: function (EnterRequest $model) {
                return '<div class="badge badge-light-secondary fw-bold">' . $model->outbounds_count . '</div>';
            })
            ->editColumn('status_name', content: function (EnterRequest $model) {
                $class = app(GetThemeType::class)->handle('bg-light-? text-?', $model->status_name);
                return '<div class="badge ' . $class . ' fw-bold">' . $model->status_name . '</div>';
            })->addColumn('action', function (EnterRequest $model) {
                $resource = 'enter_requests';
                $name = $model->bound_number;
                return view('pages.apps.operation-management.manifest-authorizations.columns._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(EnterRequest $model): QueryBuilder
    {
        return $model->select("enter_requests.*", 'enter_request_statuses.name as status_name',
            'customers.name as customer_name', 'clearance_companies.name as company_name')
            ->leftJoin('enter_request_statuses', 'enter_request_statuses.id', '=', 'enter_requests.status_id')
            ->leftJoin('customers', 'customers.id', '=', 'enter_requests.customer_id')
            ->leftJoin('clearance_companies', 'clearance_companies.id', '=', 'enter_requests.clearance_company_id')
            ->where('status_id' , EnterRequestStatus::AUTHORIZATION)
            ->when(request('customer_id'), function ($q) {
                return $q->where('customer_id', request('customer_id'));
            })->when(Arr::get(request('order'), '0.column') == 0, function ($q) {
                return $q->latest();
            })->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('manifest_authorizations_table')
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
            Column::make('bound_number')->title('Manifest Number')->addClass('text-center text-dark'),
            Column::make('customer_name')->name('customers.name')->title('Customer Name')->addClass('text-center'),
            Column::make('status_name')->title('Stage')->name('enter_request_statuses.name')->addClass('text-center'),
            Column::make('created_at')->title('Created At')->addClass('text-nowrap'),
            Column::computed('action')
                ->addClass('text-center text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible(Auth::user()->canany(['edit_enter_requests', 'delete_enter_requests']))
        ];
    }
}
