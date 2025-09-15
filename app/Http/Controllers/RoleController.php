<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Helpers\FlashHelper;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-roles')->only('index', 'show');
        $this->middleware('permission:create-roles')->only('create', 'store');
        $this->middleware('permission:edit-roles')->only('edit', 'update');
        $this->middleware('permission:delete-roles')->only('destroy');
    }

    public function index()
    {
        $roles = Role::with('permissions')->paginate(10);
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        FlashHelper::success('Role created successfully.');
        return redirect()->route('roles.index');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'required|array'
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        FlashHelper::success('Role updated successfully.');
        return redirect()->route('roles.index');
    }

    public function destroy(Role $role)
    {
        if($role->name === 'admin') {
            FlashHelper::error('Cannot delete admin role.');
            return redirect()->route('roles.index');
        }

        $role->delete();
        FlashHelper::success('Role deleted successfully.');
        return redirect()->route('roles.index');
    }
}
