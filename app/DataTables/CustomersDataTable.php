<?php

namespace App\DataTables;

use App\Models\Customer;
use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class CustomersDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['name', 'inbounds_count', 'outbounds_count'])
            ->editColumn('name', function (Customer $customer) {
                return view('pages.apps.customers._customer', compact('customer'));
            })
            ->editColumn('inbounds_count', content: function (Customer $model) {
                return '<div class="badge badge-light-secondary fw-bold">' . $model->inbounds_count . '</div>';
            })
            ->editColumn('outbounds_count', content: function (Customer $model) {
                return '<div class="badge badge-light-secondary fw-bold">' . $model->outbounds_count . '</div>';
            })
            ->addColumn('action', function (Customer $model) {
                $resource = 'customers';
                return view('pages.default._actions', compact('model','resource'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Customer $model): QueryBuilder
    {
        return $model->withCount('Inbounds')->withCount('Outbounds')->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->addIndex()
            ->setTableId('customers-table')
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
            Column::make('name')->addClass('d-flex align-items-center')->name('name'),
            Column::make('national_number')->addClass('text-center'),
            Column::make('tax_number')->addClass('text-center'),
            Column::make('company_name')->addClass('text-center'),
            Column::make('inbounds_count')->searchable(false)->orderable(false)->addClass('text-center')->title('Inbounds'),
            Column::make('outbounds_count')->searchable(false)->orderable(false)->addClass('text-center')->title('Outbounds'),
            Column::computed('action')
                ->addClass('text-center text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Users_' . date('YmdHis');
    }
}
