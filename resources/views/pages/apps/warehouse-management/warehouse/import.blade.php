<style>
    span img {
        width: 110px;
    }

    .dropzone {
        min-height: 150px;
        background: #fff;
        padding: 20px 20px;
    }

    .spinner-border {
        width: 1rem;
        height: 1rem;
    }

</style>
<form action="{{ route('partner-management.warehouse.import.store') }}" method="POST" id="importForm"
      enctype="multipart/form-data">
    @csrf
    <div class="form-group row">
        <div class="col-md-12">
            <label class="form-label" for="customFile">click to upload file </label>
            <input type="file" name="file" class="form-control "/>
            <div id="error" class="text-left mt-2"></div>
        </div>
        <div class="col-md-12 mt-5 text-center">
            <button id="btnSubmit" type="submit" class="btn btn-light-success btn-sm btnSubmit ">
                <i class="fas fa-save icon-lg"></i>
                upload
                <div id="spinner_import" class="spinner-border text-success d-none" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </button>
        </div>
    </div>
</form>
