<?php

namespace App\DataTables;

use App\Actions\GetThemeType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\WarehouseLocation;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ProductsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['name', 'unit_measure', 'items_count'])
            ->editColumn('unit_measure', content: function (Product $model) {
                return '<div class="badge badge-light-secondary fw-bold">' . $model->unit_measure . '</div>';
            })
            ->editColumn('items_count', content: function (Product $model) {
                return '<div class="badge badge-light-secondary fw-bold">' . $model->items_count . '</div>';
            })
            ->addColumn('action', function (Product $model) {
                $resource = 'products';
                return view('pages.apps.products._actions', compact('model', 'resource'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        return $model->select("products.*", 'unit_measures.name as unit_measure')
            ->leftJoin('unit_measures', 'unit_measures.id', '=', 'products.unit_measure_id')
            ->withCount('Items');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->addIndex()
            ->setTableId('products-table')
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
            Column::make('name')->addClass('text-center')->name('name'),
            Column::make('barcode')->addClass('text-center')->name('name'),
            Column::make('unit_measure')->addClass('text-center')->name('name'),
            Column::make('items_count')->searchable(false)->orderable(false)->addClass('text-center')->title('WH Items'),
            Column::computed('action')
                ->addClass('text-center text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
        ];
    }

}
