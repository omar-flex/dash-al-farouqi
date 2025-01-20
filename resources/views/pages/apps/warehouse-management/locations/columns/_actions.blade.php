<td class="text-end">
    <a class="btn btn-sm btn-light btn-active-light-info line_btn"
       id="{{$model->id}}" title="Line">
        <i class="fa-sharp-duotone fa-solid fa-shelves fa-xl"></i>
    </a>
    @can('edit_'.$resource )
        <a class="btn btn-sm btn-light btn-active-light-primary edit_btn"
           id="{{$model->id}}" aria-name="{{$name ?? 'NA'}}" title="Edit">
            <i class="fa-sharp-duotone fa-solid fa-edit fa-xl"></i>
        </a>
    @endcan
    @can('delete_'.$resource)
        <a class="btn btn-sm btn-light btn-active-light-danger remove_btn" id="{{$model->id}}" title="Delete"
           aria-name="{{$name ?? 'NA'}}">
            <i class="fa-sharp-duotone fa-solid fa-trash-alt fa-xl"></i>
        </a>
    @endcan
</td>
