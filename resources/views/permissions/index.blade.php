@extends('adminlte::page')

@section('title', 'Permissions')

@section('content_header')
    <h1>Permissions</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Permission List</h3>
            @can('create-permissions')
                <div class="card-tools">
                    <a href="{{ route('permissions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Permission
                    </a>
                </div>
            @endcan
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('success') }}
                </div>
            @endif

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $permission)
                        <tr>
                            <td>{{ $permission->name }}</td>
                            <td>
                                <div class="btn-group">
                                    @can('edit-permissions')
                                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete-permissions')
                                        <form action="{{ route('permissions.destroy', $permission) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this permission?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $permissions->links() }}
        </div>
    </div>
@stop
