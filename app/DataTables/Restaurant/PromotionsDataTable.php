<?php

namespace App\DataTables\Restaurant;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PromotionsDataTable extends DataTable
{

    public function dataTable()
    {
        $response = Http::acceptJson()->withBody(json_encode([
            'user_id' => Auth::user()->user_id,
            'restaurant_id' => $this->restaurant_id,
        ]))->get('https://ashaxisdmc.odoo.com/api/tariff/get_restaurant_details');

        if ($response->ok()) {
            $data = collect(Arr::get($response->json(), 'result.details.promotion_lines'));
        } else {
            $data = collect([]);
        }

        return (new CollectionDataTable($data))
            ->addColumn('name', function ($transportation) {
                return Arr::get($transportation, 'name.name', '-');
            })->addIndexColumn();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('restaurant_promotions-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('contract.restaurants.promotionsDataTable', ['restaurant_id' => $this->restaurant_id]))
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
            Column::make('name')->name('name.name')->title('Promotion'),
            Column::make('date_from_black_out')->title('Date From')->addClass('text-center'),
            Column::make('date_to_black_out')->title('Date To')->addClass('text-center'),
            Column::make('rate_per_person_high')->title('Rate Per Person')->addClass('text-center'),
            Column::make('rate_per_person_hb_high')->title('Rate Per Person HB')->addClass('text-center'),
            Column::make('single_supplement_high')->title('Single Supplement High')->addClass('text-center'),
            Column::make('extra_bed_supplement_high')->title('Extra Bed Supplement High')->addClass('text-center'),
            Column::make('extra_meal_supplement_high')->title('Extra Meal Supplement High')->addClass('text-center'),
        ];
    }
}
