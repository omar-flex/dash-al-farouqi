<?php

namespace App\DataTables\Hotel;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RateLinesDataTable extends DataTable
{

    public function dataTable()
    {
        $response = Http::acceptJson()->withBody(json_encode([
            'user_id' => Auth::user()->user_id,
            'hotel_id' => $this->hotel_id,
        ]))->get('https://ashaxisdmc.odoo.com/api/tariff/get_hotel_details');

        if ($response->ok()) {
            $data = collect(Arr::get($response->json(), 'result.details.rate_and_lines'));
        } else {
            $data = collect([]);
        }

        return (new CollectionDataTable($data))
            ->editColumn('pp_dbl_room_promotion', function ($hotel) {
                return number_format(Arr::get($hotel,'pp_dbl_room_promotion'),'2');
            })
            ->editColumn('pr_dbl_room_promotion', function ($hotel) {
                return number_format(Arr::get($hotel,'pr_dbl_room_promotion'),'2');
            })
            ->editColumn('pp_single_person_promotion', function ($hotel) {
                return number_format(Arr::get($hotel,'pp_single_person_promotion'),'2');
            })
            ->editColumn('pr_single_room_promotion', function ($hotel) {
                return number_format(Arr::get($hotel,'pr_single_room_promotion'),'2');
            })
            ->editColumn('pp_extra_bed_promotion', function ($hotel) {
                return number_format(Arr::get($hotel,'pp_extra_bed_promotion'),'2');
            })
            ->editColumn('pr_triple_room_promotion', function ($hotel) {
                return number_format(Arr::get($hotel,'pr_triple_room_promotion'),'2');
            })
            ->editColumn('Breakfast', function ($hotel) {
                return number_format(Arr::get($hotel,'Breakfast'),'2');
            })
            ->editColumn('lunch', function ($hotel) {
                return number_format(Arr::get($hotel,'lunch'),'2');
            })
            ->editColumn('Dinner', function ($hotel) {
                return number_format(Arr::get($hotel,'Dinner'),'2');
            })
            ->addIndexColumn();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('rate-lines-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('contract.rateLinesDataTable', ['hotel_id' => $this->hotel_id]))
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
            Column::make('DT_RowIndex')->name('id')->title('#')->addClass('text-center'),
            Column::make('name')->name('name')->title('Custom Name'),
            Column::make('date_from')->addClass('text-center'),
            Column::make('date_to')->addClass('text-center'),
            Column::make('days')->addClass('text-center'),
            Column::make('room_type.name')->name('room_type.name')->title('Room Type'),
            Column::make('pp_dbl_room_promotion')->title('Double (PP)'),
            Column::make('pr_dbl_room_promotion')->title('Double (PR)'),
            Column::make('pp_single_person_promotion')->title('Single supp'),
            Column::make('pr_single_room_promotion')->title('Single room'),
            Column::make('pp_extra_bed_promotion')->title('3rd person'),
            Column::make('pr_triple_room_promotion')->title('Triple room'),
            Column::make('Breakfast')->title('Breakfast'),
            Column::make('lunch')->title('Lunch'),
            Column::make('Dinner')->title('Dinner'),
            Column::make('is_promotion')->title('Promotion?'),
            Column::make('note')->title('Note'),
        ];
    }
}
