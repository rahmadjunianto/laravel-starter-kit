<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use App\Helpers\FlashHelper;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-permissions')->only('index', 'show');
        $this->middleware('permission:create-permissions')->only('create', 'store');
        $this->middleware('permission:edit-permissions')->only('edit', 'update');
        $this->middleware('permission:delete-permissions')->only('destroy');
    }

    public function index()
    {
        $permissions = Permission::paginate(10);
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name'
        ]);

        Permission::create(['name' => $request->name]);

        FlashHelper::success('Permission created successfully.');
        return redirect()->route('permissions.index');
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,'.$permission->id
        ]);

        $permission->update(['name' => $request->name]);

        FlashHelper::success('Permission updated successfully.');
        return redirect()->route('permissions.index');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        FlashHelper::success('Permission deleted successfully.');
        return redirect()->route('permissions.index');
    }
}
