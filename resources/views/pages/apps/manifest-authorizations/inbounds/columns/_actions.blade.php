<td class="text-end">
    @can('edit_'.$resource )
        <a class="btn btn-sm btn-light btn-active-light-primary edit_btn" title="Edit" id="{{$model->id}}" data-bound-number="{{$model->bound_number}}">
            <i class="fa-sharp-duotone fa-solid fa-edit fa-xl"></i>
        </a>
    @endcan
</td>
