<?php

namespace App\Http\Controllers\Project;

use App\Facades\MessageDakama;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Project;
use App\Models\ProjectHasLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()->first(),
                'data' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $query = ProjectHasLocation::query()->where('project_id', $request->project_id);

        $locations = $query->paginate($request->per_page);

        return LocationResource::collection($locations);
    }

    public function show($id)
    {
        $location = ProjectHasLocation::find($id);
        if (!$location) {
            return MessageDakama::notFound('data not found!');
        }

        return new LocationResource($location);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:100',
            'project_id' => 'required|exists:projects,id',
            'longitude' => 'required|max:50',
            'latitude' => 'required|max:50',
            'radius' => 'required|max:50',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $project = Project::find($request->project_id);

        try {
            if ($request->is_default) {
                $project->locations()->update([
                    'is_default' => false
                ]);
            }

            $location = $project->locations()->create([
                'name' => $request->name,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'radius' => $request->radius ?? 6,
                'is_default' => $request->is_default ?? false
            ]);

            DB::commit();
            return MessageDakama::success("location has been created", $location);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $location = ProjectHasLocation::find($id);
        if (!$location) {
            return MessageDakama::notFound('data not found!');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:100',
            'longitude' => 'required|max:50',
            'latitude' => 'required|max:50',
            'radius' => 'required|max:50',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if ($request->is_default) {
                ProjectHasLocation::where('project_id', $location->project_id)->update([
                    'is_default' => false
                ]);
            }

            $location->update([
                'name' => $request->name,
                'longitude' => $request->longitude,
                'latitude' => $request->latitude,
                'radius' => $request->radius ?? 6,
                'is_default' => $request->is_default ?? false
            ]);

            DB::commit();
            return MessageDakama::success("location has been updated", $location);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        $location = ProjectHasLocation::find($id);
        if (!$location) {
            return MessageDakama::notFound('data not found!');
        }

        if (ProjectHasLocation::count() < 2) {
            return MessageDakama::warning("you can't delete the last location");
        }

        try {
            if ($location->is_default) {
                ProjectHasLocation::where('project_id', $location->project_id)->first()->update([
                    'is_default' => true
                ]);
            }

            $location->delete();
            DB::commit();
            return MessageDakama::success("location has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
