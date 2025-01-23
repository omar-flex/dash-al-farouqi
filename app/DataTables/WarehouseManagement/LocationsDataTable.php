<?php

namespace App\DataTables\WarehouseManagement;


use App\Actions\GetThemeType;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LocationsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['wh_name', 'code'])
            ->editColumn('code', content: function (WarehouseLocation $model) {
                $class = app(GetThemeType::class)->handle('bg-?', $model->code);
                return '<div class="badge text-white ' . $class . ' fw-bold">' . $model->code . '</div>';
            })
            ->editColumn('wh_name', content: function (WarehouseLocation $model) {
                $class = app(GetThemeType::class)->handle('bg-light-? text-?', $model->wh_name);
                return '<div class="badge ' . $class . ' fw-bold">' . $model->wh_name . '</div>';
            })
            ->editColumn('total_capacity', function (WarehouseLocation $model) {
                return $model->total_capacity . ' cpm';
            })
            ->addColumn('action', function (WarehouseLocation $model) {
                $resource = 'locations';
                $name = $model->name;
                return view('pages.apps.warehouse-management.locations.columns._actions', compact('model', 'resource', 'name'));
            })->addIndexColumn();
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(WarehouseLocation $model): QueryBuilder
    {
        $capacitySubquery = DB::table('location_lines')
            ->select('location_id', DB::raw('SUM(capacity) as total_capacity'))
            ->groupBy('location_id');


        return $model->select("warehouse_locations.*",
            DB::raw("CONCAT(warehouses.name, ' - ', warehouses.code) AS wh_name"),
            DB::raw('COALESCE(capacity_sums.total_capacity, 0) as total_capacity'))
            ->leftJoin('warehouses', 'warehouses.id', '=', 'warehouse_locations.warehouse_id')
            ->leftJoinSub($capacitySubquery, 'capacity_sums', function ($join) {
                $join->on('capacity_sums.location_id', '=', 'warehouse_locations.id');
            })->withCount('Lines')
            ->when(Arr::get(request('order'), '0.column') == 0, function ($q) {
                return $q->latest();
            })->when(request('warehouse_id'), function ($q) {
                return $q->where('warehouse_id', request('warehouse_id'));
            })->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('locations-table')
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
            Column::make('name')->title('Name')->addClass('text-center'),
            Column::make('code')->title('Code')->addClass('text-center'),
            Column::make('position')->addClass('text-center'),
            Column::make('wh_name')->title('Warehouse')->addClass('text-center'),
            Column::make('total_capacity')->title('Total Capacity')->addClass('text-center'),
            Column::make('lines_count')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->visible(Auth::user()->canany(['edit_locations', 'delete_locations']))
        ];
    }
}
