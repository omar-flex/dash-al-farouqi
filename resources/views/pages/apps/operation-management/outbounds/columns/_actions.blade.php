<td class="text-end">
    <a class="btn btn-sm btn-light btn-light-info output_products_pdf_btn @if($model->status_id < \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT) pe-none @endif" @if($model->status_id < \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT) disabled @endif  title="pdf" target="_blank" id="{{$model->id}}">
        <i class="fa-sharp-duotone fa-solid fa-truck-container fa-flip-horizontal fa-xl"></i>
    </a>
    <a class="btn btn-sm btn-light btn-light-google"
       href="{{route('operation-management.outbounds.pdf',$model->id)}}" title="pdf" target="_blank">
        <i class="fa-sharp-duotone fa-solid fa-file-pdf fa-xl"></i>
    </a>
    @can('edit_'.$resource )
        <a class="btn btn-sm btn-light btn-active-light-primary edit_btn" title="Edit" id="{{$model->id}}">
            <i class="fa-sharp-duotone fa-solid fa-edit fa-xl"></i>
        </a>
    @endcan

        @can('delete_'.$resource)
            <a class="btn btn-sm btn-light btn-active-light-danger remove_btn  @if($model->status_id != \App\Models\EnterRequestStatus::DRAFT)  @endif"
               @if($model->status_id != \App\Models\EnterRequestStatus::DRAFT)
               style="pointer-events: none; cursor: default;"
               @endif
               id="{{$model->id}}" title="Delete"
               aria-name="{{$name ?? 'NA'}}">
                <i class="fa-sharp-duotone fa-solid fa-trash-alt fa-xl"></i>
            </a>
        @endcan

</td>
