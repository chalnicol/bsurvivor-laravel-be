<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Http\Resources\RoleResource;

class RoleController extends Controller
{
    
     public function getAllRoles()
    {
        $roles = Role::all();

        // If you want to include permissions associated with each role:
        // $roles = Role::with('permissions')->get();

        return response()->json([
            'message' => 'Roles fetched successfully.',
            'roles' => RoleResource::collection($roles) // Use the collection
        ]);
    }

    public function getAllRolesWithPermissions()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'message' => 'Roles with permissions fetched successfully.',
            'roles' => $roles
        ]);
    }

}