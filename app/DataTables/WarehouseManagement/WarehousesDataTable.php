<?php

namespace App\DataTables\WarehouseManagement;


use App\Actions\GetThemeType;
use App\Models\LocationLine;
use App\Models\Partner;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class WarehousesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['is_active', 'code'])
            ->editColumn('code', content: function (Warehouse $model) {
                return '<div class="badge badge-light-dark fw-bold">' . $model->code . '</div>';
            })
            ->editColumn('is_active', function (Warehouse $model) {
                $class = $model->is_active ? 'badge-light-primary' : 'badge-light-danger';
                return sprintf('<div class="badge  ' . $class . ' fw-bold">%s</div>', $model->is_active ? 'Active' : 'Inactive');
            })->addColumn('total_capacity', function (Warehouse $model) {
                $locations_ids = $model->Locations()->pluck('id')->toArray();
                $total_capacity = LocationLine::whereIn('location_id', $locations_ids)->sum('capacity');
                return $total_capacity . ' cpm';
            })
            ->addColumn('action', function (Warehouse $model) {
                $resource = 'warehouses';
                $name = $model->name;
                return view('pages.default._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Warehouse $model): QueryBuilder
    {
        return $model->withCount('Locations')->when(Arr::get(request('order'), '0.column') == 0, function ($q) {
            return $q->latest();
        })->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('warehouses-table')
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
            Column::make('name'),
            Column::make('code'),
            Column::make('locations_count')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('total_capacity')->searchable(false)->orderable(false)->addClass('text-center'),
            //Column::make('is_active')->title('Status')->addClass('text-center'),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible(Auth::user()->canany(['edit_warehouses', 'delete_warehouses']))
        ];
    }
}
