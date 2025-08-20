<?php

namespace App\DataTables\OperationManagement;


use App\Actions\GetThemeType;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
            ->rawColumns(['status_name', 'bound_number', 'outbounds_count', 'customer_name'])
            ->filterColumn('product_names', function ($query, $keyword) {
                $query->havingRaw('GROUP_CONCAT(DISTINCT products.name) LIKE ?', ["%{$keyword}%"]);
            })
            ->editColumn('product_names', function ($row) {
                return $row->product_names ?? '—';
            })
            ->editColumn('customer_name', content: function (EnterRequest $model) {
                return '<div class="d-flex align-items-center justify-content-center">
                    <div class="d-flex flex-column">
                     <span class="fw-bold">' . $model->customer_name . '</span>
                     <span class="text-muted">' . $model->company_name . '</span>
                   </div> </div>';
            })
            ->editColumn('invoicing_date', function (EnterRequest $model) {
                return $model->invoicing_date ? Carbon::parse($model->invoicing_date)->format('d M Y') : '---';
            })
            ->editColumn('created_at', function (EnterRequest $model) {
                return $model->created_at->format('d M Y');
            })
            ->editColumn('total_cost', content: function (EnterRequest $model) {
                return number_format($model->total_cost, '2');
            })
            ->editColumn('net_weight', content: function (EnterRequest $model) {
                return number_format($model->net_weight, '2');
            })
            ->editColumn('gross_weight', content: function (EnterRequest $model) {
                return number_format($model->gross_weight, '2');
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
                return view('pages.apps.operation-management.enter-requests.columns._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(EnterRequest $model): QueryBuilder
    {
        return $model->selectRaw('enter_requests.*,
                                        enter_request_statuses.name as status_name,
                                        customers.name as customer_name,
                                        clearance_companies.name as company_name,
                                        GROUP_CONCAT(DISTINCT products.name SEPARATOR ", ") as product_names')
            ->leftJoin('enter_request_statuses', 'enter_request_statuses.id', '=', 'enter_requests.status_id')
            ->leftJoin('customers', 'customers.id', '=', 'enter_requests.customer_id')
            ->leftJoin('clearance_companies', 'clearance_companies.id', '=', 'enter_requests.clearance_company_id')
            ->leftJoin('warehouse_items', 'warehouse_items.enter_request_id', '=', 'enter_requests.id')
            ->leftJoin('products', 'products.id', '=', 'warehouse_items.product_id')
            ->groupBy('enter_requests.id')
            ->withCount('Outbounds')
            ->when(Auth::user()->hasRole('customer'), function ($query) {
                return $query->where('customer_id', Auth::user()->customer?->id)
                    ->where('status_id', EnterRequestStatus::APPROVED);
            })
            ->when(request('customer_id'), function ($q) {
                return $q->where('customer_id', request('customer_id'));
            })
            ->when(request('company_id'), function ($q) {
                return $q->where('clearance_company_id', request('company_id'));
            })
            ->when(request('status_id'), function ($q) {
                return $q->where('status_id', request('status_id'));
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
        return array_merge(
            $this->getBasicColumns(),
            $this->getAdministratorColumns(),
            $this->getHiddenColumns(),
            $this->getActionColumn()
        );
    }

    private function getBasicColumns(): array
    {
        return [
            $this->configureColumn('DT_RowIndex', [
                'name' => 'id',
                'title' => '#',
                'class' => 'text-center'
            ]),
            $this->configureColumn('bound_number', [
                'title' => 'Bound Number',
                'class' => 'text-center text-dark'
            ]),
            $this->configureColumn('customer_name', [
                'name' => 'customers.name',
                'title' => 'Customer Name',
                'class' => 'text-center',
                 'visible' => !Auth::user()->hasRole('customer')
            ]),
            $this->configureColumn('net_weight', [
                'title' => 'Net weight',
                'class' => 'text-center'
            ]),
            $this->configureColumn('gross_weight', [
                'title' => 'Gross Weight',
                'class' => 'text-center'
            ]),
            $this->configureColumn('total_cost', [
                'title' => 'Total Cost',
                'class' => 'text-center'
            ]),
            $this->configureColumn('cpm_result', [
                'title' => 'CPM',
                'class' => 'text-center'
            ]),
            $this->configureColumn('status_name', [
                'name' => 'enter_request_statuses.name',
                'title' => 'Stage',
                'class' => 'text-center',
                'visible' => !Auth::user()->hasRole('customer')
            ]),
            $this->configureColumn('created_at', [
                'title' => 'Created At',
                'class' => 'text-nowrap'
            ])
        ];
    }

    private function getAdministratorColumns(): array
    {
        if (!auth()->user()->hasRole('administrator')) {
            return [];
        }

        return [
            $this->configureColumn('invoicing_date', [
                'title' => 'Invoicing Date',
                'class' => 'text-nowrap'
            ])
        ];
    }

    private function getHiddenColumns(): array
    {
        return [
            $this->configureColumn('product_names', [
                'name' => 'products.name',
                'class' => 'text-nowrap',
                'visible' => false
            ]),
            $this->configureColumn('outbounds_count', [
                'title' => 'Outbounds',
                'class' => 'text-center',
                'searchable' => false,
                'orderable' => false
            ])
        ];
    }

    private function getActionColumn(): array
    {
        $userCanManageRequests = Auth::user()->canany(['edit_enter_requests', 'delete_enter_requests']);

        return [
            Column::computed('action')
                ->addClass('text-center text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible($userCanManageRequests)
        ];
    }

    private function configureColumn(string $name, array $config): Column
    {
        $column = Column::make($name);

        if (isset($config['name'])) {
            $column->name($config['name']);
        }
        if (isset($config['title'])) {
            $column->title($config['title']);
        }
        if (isset($config['class'])) {
            $column->addClass($config['class']);
        }
        if (isset($config['visible'])) {
            $column->visible($config['visible']);
        }
        if (isset($config['searchable'])) {
            $column->searchable($config['searchable']);
        }
        if (isset($config['orderable'])) {
            $column->orderable($config['orderable']);
        }

        return $column;
    }
}
