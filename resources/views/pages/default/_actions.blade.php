<td class="text-end">
        @can('edit_'.$resource ?? null )
            <a class="btn btn-sm btn-light btn-active-light-primary edit_btn"
               id="{{$model->id}}" title="Edit">
                <i class="fas fa-edit  text-hover-primary fa-xl"></i>
            </a>
        @endcan
        @can('delete_'.$resource ?? null )
            <a class="btn btn-sm btn-light btn-active-light-danger remove_btn" id="{{$model->id}}" title="Delete" aria-name="{{$name ?? 'NA'}}">
                <i class="fas fa-trash-alt text-hover-danger fa-xl "></i>
            </a>
        @endcan

</td>
