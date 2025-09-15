@extends('adminlte::page')

@section('title', 'Edit Role')

@section('content_header')
    <h1>Edit Role</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required {{ $role->name === 'admin' ? 'readonly' : '' }}>
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Permissions</label>
                    <select class="select2" multiple="multiple" name="permissions[]" id="permissions">
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->name }}" {{ $role->hasPermissionTo($permission->name) ? 'selected' : '' }}>
                                {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('permissions')
                        <div class="text-danger">
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link href="/vendor/select2/css/select2.min.css" rel="stylesheet" />
    <link href="/vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css" rel="stylesheet" />
@stop

@section('js')
    <script src="/vendor/select2/js/select2.full.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#permissions').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select permissions',
                allowClear: true
            });
        });
    </script>
@stop
