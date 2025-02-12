<td class="text-end">
    <a class="btn btn-sm btn-light btn-light-google edit_btn"
       href="{{route('operation-management.enter_requests.pdf',$model->id)}}" title="pdf" target="_blank">
        <i class="fa-sharp-duotone fa-solid fa-file-pdf fa-xxl"></i>
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
