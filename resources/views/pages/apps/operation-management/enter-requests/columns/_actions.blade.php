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
    @if($model->status_id == \App\Models\EnterRequestStatus::DRAFT)
        @can('delete_'.$resource)
            <a class="btn btn-sm btn-light btn-active-light-danger  remove_btn" id="{{$model->id}}" title="Delete"
               aria-name="{{$name ?? 'NA'}}">
                <i class="fa-sharp-duotone fa-solid fa-trash-alt fa-xl"></i>
            </a>
        @endcan
    @endif
</td>
