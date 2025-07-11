<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function __construct(
        protected Role $role,
        protected Permission $permission,
    ) {}

    public function index(Request $request)
    {
        $query = $this->permission->query()->select('id', 'name', 'parent_id');

        if ($request->filled('permission_id')) {
            $permission = $query->where('id', $request->permission_id);

            if ($permission->doesntExist()) {
                return MessageDakama::warning('Permission does not exist');
            }

            return new PermissionResource($permission->first());
        }

        $query->whereNull('parent_id');
        $query->with(['children']);

        $permissions = $query->get();

        return PermissionResource::collection($permissions);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name',
            'parent_id' => 'nullable|exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $permission = $this->permission->create([
                'name' => ucwords($request->name),
                'guard_name' => 'api',
                'parent_id' => $request->parent_id ?? null
            ]);
            DB::commit();
            return MessageDakama::render([
                'message' => 'Permission created successfully',
                'status' => MessageDakama::SUCCESS,
                'status_code' => MessageDakama::HTTP_CREATED,
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }

    public function assign(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $permission = $this->permission->findById($request->permission_id, 'api');
        $roleHasPermission = $this->role->findById($request->role_id, 'api')->hasPermissionTo($permission);
        if ($roleHasPermission) {
            return MessageDakama::warning('Permission already exist');
        }

        try {
            $this->role->findById($request->role_id, 'api')->givePermissionTo($permission);

            DB::commit();
            return MessageDakama::success('Permission assigned successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function unassign(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $permission = $this->permission->findById($request->permission_id, 'api');
        $roleHasPermission = $this->role->findById($request->role_id, 'api')->hasPermissionTo($permission);
        if (!$roleHasPermission) {
            return MessageDakama::warning('Permission does not exist');
        }

        try {
            $this->role->findById($request->role_id, 'api')->revokePermissionTo($permission);

            DB::commit();
            return MessageDakama::success('Permission unassigned successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        $permission = $this->permission->find($id);
        if (!$permission) {
            return MessageDakama::warning('Permission does not exist');
        }

        try {
            $permission->delete();
            DB::commit();
            return MessageDakama::success('Permission deleted successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $permission = $this->permission->find($id);
        if (!$permission) {
            return MessageDakama::warning('Permission does not exist');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $permission->update($validator->validated());

            DB::commit();
            return MessageDakama::render([
                'message' => 'Permission updated successfully',
                'status' => MessageDakama::SUCCESS,
                'status_code' => MessageDakama::HTTP_OK,
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }
}
