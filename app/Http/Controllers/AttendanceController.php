<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Requests\Attendance\StoreRequest;
use App\Http\Resources\Attendance\AdjustmentCollection;
use App\Http\Resources\Attendance\AttendanceCollection;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\Role;
use App\Models\UserProjectAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Attendance::query();

        $query->with(['project', 'user', 'task']);

        if ($user->hasRole(Role::KARYAWAN)) {
            $query->where('user_id', $user->id);
        }

        $query->when($request->has('project_id') && $request->filled('project_id'), function ($query) use ($request) {
            $query->where('project_id', $request->project_id);
        });

        $query->when($request->has('task_id') && $request->filled('task_id'), function ($query) use ($request) {
            $query->where('task_id', $request->task_id);
        });

        $query->when($request->has('date') && $request->filled('date'), function ($query) use ($request) {
            $query->whereDate('start_time', $request->date);
        });

        $query->when($request->has('status') && $request->filled('status'), function ($query) use ($request) {
            $query->where('status', $request->status);
        });

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $adjs = $query->paginate($request->per_page);
        } else {
            $adjs = $query->get();
        }


        return new AttendanceCollection($adjs);
    }

    public function showMe()
    {
        $user = Auth::user();

        $currentTime = now();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('start_time', $currentTime)->first();

        if (!$attendance) {
            return MessageDakama::warning("User not attendance now!");
        }

        if ($attendance->status == Attendance::ATTENDANCE_OUT) {
            return MessageDakama::warning("User already attendance out!");
        }

        return new AttendanceResource($attendance->first());
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $projectAbsen = UserProjectAbsen::where([
            'project_id' => $request->validated('project_id'),
            'user_id'    => $user->id
        ])->first();
        if (!$projectAbsen) {
            return MessageDakama::warning("User not registered in the project '{$request->validated('project_id')}'!");
        }

        $currentTime = now();

        $attendance = Attendance::where([
            'project_id' => $request->validated('project_id'),
            'user_id'    => $user->id
        ])->whereDate('start_time', $currentTime)->first();

        if ($attendance && $attendance->status == Attendance::ATTENDANCE_OUT) {
            return MessageDakama::warning("User already attendance out!");
        }

        if ($attendance) {
            $validator = Validator::make($request->all(), [
                'location_out' => 'required',
                'location_lat_out' => 'required',
                'location_long_out' => 'required',
            ]);

            if ($validator->fails()) {
                return MessageDakama::render([
                    'status' => "ATTENDANCE_OUT_WARNING",
                    'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => $validator->errors()
                ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            $validator = Validator::make($request->all(), [
                'location_in' => 'required',
                'location_lat_in' => 'required',
                'location_long_in' => 'required',
            ]);

            if ($validator->fails()) {
                return MessageDakama::render([
                    'status' => "ATTENDANCE_IN_WARNING",
                    'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => $validator->errors()
                ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {

            if ($attendance) {
                $attendance->update([
                    'end_time' => $currentTime,
                    'location_out' => $request->location_out,
                    'location_lat_out' => $request->location_lat_out,
                    'location_long_out' => $request->location_long_out,
                    'image_out' => $request->file('image')->store(Attendance::ATTENDANCE_IMAGE_OUT, 'public'),
                    'status' => Attendance::ATTENDANCE_OUT
                ]);
            } else {
                $attendance = Attendance::create([
                    'project_id' => $request->validated('project_id'),
                    'task_id' => $request->validated('task_id'),
                    'start_time' => $currentTime,
                    'location_in' => $request->location_in,
                    'location_lat_in' => $request->location_lat_in,
                    'location_long_in' => $request->location_long_in,
                    'user_id' => $user->id,
                    'duration' => $projectAbsen->duration,
                    'image_in' => $request->file('image')->store(Attendance::ATTENDANCE_IMAGE_IN, 'public'),
                    'status' => Attendance::ATTENDANCE_IN
                ]);
            }

            DB::commit();
            return MessageDakama::success("Berhasil mendaftarkan absen: " . $user->name);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal mendaftarkan absen: " . $user->name);
        }
    }

    public function adjustmentIndex(Request $request)
    {
        $query = AttendanceAdjustment::query();

        $query->with(['pic', 'attendance', 'user']);

        $query->when($request->has('status') && $request->filled('status'), function ($query) use ($request) {
            $query->where('status', $request->status);
        });

        $query->when($request->has('pic_id') && $request->filled('pic_id'), function ($query) use ($request) {
            $query->where('pic_id', $request->pic_id);
        });

        $adjs = $query->paginate($request->per_page);

        return new AdjustmentCollection($adjs);
    }

    public function adjustmentStore(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'pic_id' => 'required|exists:users,id',
            'attendance_id' => 'required|exists:attendances,id',
            'new_start_time' => 'required|date_format:Y-m-d H:i:s',
            'new_end_time' => 'required|date_format:Y-m-d H:i:s',
            'reason' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->id == $request->pic_id) {
            return MessageDakama::warning("You can't adjust your own attendance!");
        }

        $attendanceAdj = AttendanceAdjustment::where('attendance_id', $request->attendance_id)->first();
        if ($attendanceAdj) {
            return MessageDakama::warning("Adjustment already exist!");
        }

        $attendance = Attendance::where('id', $request->attendance_id)->first();
        if ($attendance->status != Attendance::ATTENDANCE_OUT) {
            return MessageDakama::warning("User not attendance out!");
        }

        try {
            AttendanceAdjustment::create([
                'attendance_id' => $request->attendance_id,
                'pic_id' => $request->pic_id,
                'user_id' => $user->id,
                'new_start_time' => $request->new_start_time,
                'new_end_time' => $request->new_end_time,
                'old_start_time' => $attendance->start_time,
                'old_end_time' => $attendance->end_time,
                'reason' => $request->reason
            ]);

            DB::commit();
            return MessageDakama::success("Adjustment has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function adjustmentUpdate(Request $request, $id)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $attendanceAdj = AttendanceAdjustment::find($id);
        if (!$attendanceAdj) {
            return MessageDakama::warning("Adjustment not found!");
        }

        $validator = Validator::make($request->all(), [
            'pic_id' => 'required|exists:users,id',
            'attendance_id' => 'required|exists:attendances,id',
            'new_start_time' => 'required|date_format:Y-m-d H:i:s',
            'new_end_time' => 'required|date_format:Y-m-d H:i:s',
            'reason' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->id == $request->pic_id) {
            return MessageDakama::warning("You can't adjust your own attendance!");
        }

        $attendance = Attendance::where('id', $request->attendance_id)->first();

        try {
            $attendanceAdj->update([
                'attendance_id' => $request->attendance_id,
                'pic_id' => $request->pic_id,
                'new_start_time' => $request->new_start_time,
                'new_end_time' => $request->new_end_time,
                'old_start_time' => $attendance->start_time,
                'old_end_time' => $attendance->end_time,
                'reason' => $request->reason
            ]);

            DB::commit();
            return MessageDakama::success("Adjustment has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function adjustmentApproval(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'adjustment_id' => 'required|exists:attendance_adjustments,id',
            'status' => 'required|in:approved,rejected,cancelled',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $adj = AttendanceAdjustment::find($request->adjustment_id);

        try {
            if ($request->status == AttendanceAdjustment::STATUS_APPROVED) {
                $adj->attendance()->update([
                    'start_time' => $adj->new_start_time,
                    'end_time' => $adj->new_end_time
                ]);
            }

            if ($request->status == AttendanceAdjustment::STATUS_CANCELLED) {
                $adj->attendance()->update([
                    'start_time' => $adj->old_start_time,
                    'end_time' => $adj->old_end_time
                ]);
            }

            $adj->update([
                'status' => $request->status
            ]);

            DB::commit();
            return MessageDakama::success("Adjustment has been {$request->status}");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function adjustmentDestroy($id)
    {
        $adj = AttendanceAdjustment::find($id);

        if (!$adj) {
            return MessageDakama::warning("Adjustment not found!");
        }

        if ($adj->status != AttendanceAdjustment::STATUS_WAITING) {
            return MessageDakama::warning("Adjustment has been {$adj->status}, can't delete!");
        }

        try {
            $adj->delete();
            return MessageDakama::success("Adjustment has been deleted");
        } catch (\Throwable $th) {
            return MessageDakama::error($th->getMessage());
        }
    }
}
