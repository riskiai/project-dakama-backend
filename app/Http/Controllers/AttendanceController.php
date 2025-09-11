<?php

namespace App\Http\Controllers;

use App\Facades\Helper;
use App\Facades\MessageDakama;
use App\Http\Requests\Attendance\StoreRequest;
use App\Http\Resources\Attendance\AdjustmentCollection;
use App\Http\Resources\Attendance\AttendanceCollection;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\OperationalHour;
use App\Models\Overtime;
use App\Models\Project;
use App\Models\Role;
use App\Models\UserProjectAbsen;
use App\Models\UserSalary;
use Carbon\Carbon;
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

        $query->with([
            'project',
            'user',
            'budget' => function ($query) {
                $query->withTrashed();
            },
            'overtime',
        ]);

        if ($user->hasRole(Role::KARYAWAN)) {
            $query->where('user_id', $user->id);
        }

        $query->when($request->has('project_id') && $request->filled('project_id'), function ($query) use ($request) {
            $query->where('project_id', $request->project_id);
        });

        $query->when($request->has('budget_id') && $request->filled('budget_id'), function ($query) use ($request) {
            $query->where('budget_id', $request->budget_id);
        });

        $query->when($request->has('status') && $request->filled('status'), function ($query) use ($request) {
            $query->where('status', $request->status);
        });

        $query->when($request->has('user_id') && $request->filled('user_id'), function ($query) use ($request) {
            $query->where('user_id', $request->user_id);
        });

        $query->when($request->has('search') && $request->filled('search'), function ($query) use ($request) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            });
        });

        $query->when($request->has('start_date') && $request->filled('start_date') && $request->has('end_date') && $request->filled('end_date'), function ($query) use ($request) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        });

        $query->when($request->has('type') && $request->filled('type'), function ($query) use ($request) {
            $query->where('type', $request->type);
        });

        $query->when($request->has('sort_by') && $request->filled('sort_by') && $request->has('sort_type') && $request->filled('sort_type'), function ($query) use ($request) {
            $query->orderBy($request->sort_by ?? 'id', $request->sort_type ?? 'desc');
        });

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $adjs = $query->paginate($request->per_page);
        } else {
            $adjs = $query->get();
        }

        return new AttendanceCollection($adjs);
    }

    public function show($id)
    {
        $attendance = Attendance::with([
            'budget' => function ($query) {
                $query->withTrashed();
            },
            'overtime',
        ])->first();
        if (!$attendance) {
            return MessageDakama::notFound('Attendance not found');
        }

        return new AttendanceResource($attendance);
    }

    public function showMe(Request $request)
    {
        $user = Auth::user();

        $currentTime = now();

        $query = Attendance::with([
            'budget' => function ($query) {
                $query->withTrashed();
            },
            'overtime',
        ])
            ->where('user_id', $user->id)
            ->whereDate('start_time', $currentTime->toDateString())
            ->where('type', $request->type ?? 0);

        if ($request->type == 0) {
            $query->where('end_time', '>', $currentTime);
        }

        if ($request->type == 1) {
            $query->whereNull('end_time');
        }

        $attendance = $query->first();

        if (!$attendance) {
            return MessageDakama::warning("User not attendance now!");
        }

        return new AttendanceResource($attendance);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        $operationalHour = Project::find($request->validated('project_id'))->operationalHour;
        if (!$operationalHour) {
            return MessageDakama::warning("Operational hour not found!");
        }

        $user = Auth::user()->load('salary');
        if (!$user->salary) {
            return MessageDakama::warning("User not registered of a salary!");
        }

        $projectAbsen = UserProjectAbsen::where([
            'project_id' => $request->validated('project_id'),
            'user_id'    => $user->id
        ])->first();
        if (!$projectAbsen) {
            return MessageDakama::warning("User not registered in the project '{$request->validated('project_id')}'!");
        }

        $currentTime = now();
        $endTime = Carbon::parse($currentTime->format('Y-m-d') . ' ' . $operationalHour->offtime);

        $attendanceCurrent = Attendance::where([
            'project_id' => $request->validated('project_id'),
            'user_id'    => $user->id,
            'budget_id'  => $request->validated('budget_id'),
            'type'       => $request->validated('type')
        ])->whereDate('start_time', $currentTime)->first();
        if ($attendanceCurrent && $attendanceCurrent->type == Attendance::ATTENDANCE_TYPE_NORMAL) {
            return MessageDakama::warning("User already attendance now!");
        }

        if ($attendanceCurrent && $attendanceCurrent->type == Attendance::ATTENDANCE_TYPE_OVERTIME && $attendanceCurrent->end_time != null) {
            return MessageDakama::warning("User already attendance out!");
        }

        try {
            $attendance = null;

            if ($request->validated('type') == Attendance::ATTENDANCE_TYPE_NORMAL) {
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

                $duration = round($currentTime->diffInHours($endTime, false), 0);

                $lateCut = 0;
                if (strtotime($currentTime) > strtotime(Carbon::parse($currentTime->format('Y-m-d') . ' ' . $operationalHour->late_time))) {
                    $lateCut = Helper::calculateLateCut($user->salary->daily_salary, abs($currentTime->diffInMinutes($operationalHour->late_time)));
                }

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'budget_id' => $request->validated('budget_id'),
                    'project_id' => $request->validated('project_id'),
                    'location_in' => $request->location_in,
                    'location_lat_in' => $request->location_lat_in,
                    'location_long_in' => $request->location_long_in,
                    'start_time' => $currentTime,
                    'end_time' => $operationalHour->offtime,
                    'image_in' => $request->file('image')->store(Attendance::ATTENDANCE_IMAGE_IN, 'public'),
                    'duration' => $duration,
                    'daily_salary' => $user->salary->hourly_salary * $duration,
                    'type' => 0,
                    'late_cut' => $lateCut,
                    'late_minutes' => $lateCut != 0 ? abs($currentTime->diffInMinutes($operationalHour->late_time)) : 0
                ]);
            } else {
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

                $duration = round($endTime->diffInHours($currentTime, false), 0);

                $attendanceCurrent->update([
                    'location_out' => $request->location_out,
                    'location_lat_out' => $request->location_lat_out,
                    'location_long_out' => $request->location_long_out,
                    'end_time' => $currentTime,
                    'image_out' => $request->file('image')->store(Attendance::ATTENDANCE_IMAGE_OUT, 'public'),
                    'duration' => $duration,
                    'hourly_overtime_salary' => $user->salary->hourly_overtime_salary * $duration,
                    'type' => 1
                ]);

                $attendance = $attendanceCurrent;
            }

            DB::commit();
            return MessageDakama::success("Berhasil absen", $attendance);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal absen: " . $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        if (Auth::user()->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning("You can't update attendance!");
        }

        $attendance = Attendance::find($id)->load(['project.operationalHour', 'user.salary']);
        if (!$attendance) {
            return MessageDakama::warning("Attendance not found!");
        }

        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $attendance->user;
        $operationalHour = $attendance->project->operationalHour;
        if (!$operationalHour) {
            return MessageDakama::warning("Operational hour not found!");
        }

        $startTime = Carbon::parse($attendance->start_time->format('Y-m-d') . ' ' . $request->start_time);
        $endTime = Carbon::parse($attendance->end_time->format('Y-m-d') . ' ' . $request->end_time);

        if ($attendance->type == Attendance::ATTENDANCE_TYPE_NORMAL) {
            if (strtotime($endTime) > strtotime(Carbon::parse($attendance->end_time->format('Y-m-d') . ' ' . $operationalHour->offtime))) {
                return MessageDakama::warning("End time must be before offtime!");
            }

            if (strtotime($startTime) < strtotime(Carbon::parse($attendance->end_time->format('Y-m-d') . ' ' . $operationalHour->ontime_start))) {
                return MessageDakama::warning("Start time must be after ontime start!");
            }
        }

        $lateCut = 0;
        if (strtotime($startTime) > strtotime(Carbon::parse($attendance->end_time->format('Y-m-d') . ' ' . $operationalHour->late_time))) {
            $lateCut = Helper::calculateLateCut($user->salary->daily_salary, abs($startTime->diffInMinutes($operationalHour->late_time)));
        }


        try {
            $duration = round($startTime->diffInHours($endTime, false), 0);

            $data = [
                ...$validator->validated(),
                'duration' => $duration,
            ];


            if ($attendance->type == Attendance::ATTENDANCE_TYPE_OVERTIME) {
                $data = [
                    ...$data,
                    'hourly_overtime_salary' => $user->salary->hourly_overtime_salary * $duration
                ];
            } else {
                $data = [
                    ...$data,
                    'late_cut' => $lateCut,
                    'late_minutes' => $lateCut != 0 ? abs($startTime->diffInMinutes($operationalHour->late_time)) : 0,
                    'daily_salary' => $user->salary->hourly_salary * $duration,
                ];
            }

            $attendance->update($data);

            DB::commit();
            return MessageDakama::success("Berhasil update absen", $attendance);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal update absen: " . $th->getMessage());
        }
    }

    public function sync(Request $request)
    {
        abort(404);

        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = UserSalary::where('user_id', $request->user_id)->first();
        if (!$user) {
            return MessageDakama::warning("User not registered of a salary!");
        }

        $attendances = Attendance::where('user_id', $request->user_id)->whereBetween('start_time', [Carbon::parse($request->start_date), Carbon::parse($request->end_date)->addHours(24)])->get();
        if (!$attendances) {
            return MessageDakama::warning("User not attendance!");
        }

        $attendNormalIds = $attendances->where('type', Attendance::ATTENDANCE_TYPE_NORMAL)->pluck('id')->toArray();
        $attendOverTimeIds = $attendances->where('type', Attendance::ATTENDANCE_TYPE_OVERTIME)->pluck('id')->toArray();
        try {
            Attendance::whereIn('id', $attendanceIds)->update([
                'daily_salary' => $user->daily_salary,
                'hourly_salary' => $user->hourly_salary,
                'hourly_overtime_salary' => $user->hourly_overtime_salary,
                'transport' => $user->transport,
                'makan' => $user->makan,
            ]);

            foreach ($attendances as $attendance) {
                if ($attendance->late_cut > 0) {
                    $lateCut = Helper::calculateLateCut(
                        $user->daily_salary,
                        $attendance->late_minutes
                    );

                    $attendance->update(['late_cut' => $lateCut]);
                }
            }

            DB::commit();
            return MessageDakama::success("Berhasil sinkronisasi gaji");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal sinkronisasi gaji" . $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $attendances = Attendance::whereBetween('created_at', [$request->start_date, $request->end_date])->where('is_settled', 1);
        if ($attendances->count() < 1) {
            return MessageDakama::warning("Absen tidak ada diantara tanggal tersebut!");
        }

        try {
            $attendances->delete();

            DB::commit();
            return MessageDakama::success("Berhasil menghapus absen");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal menghapus absen" . $th->getMessage());
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

        $query->when($request->has('sort_by') && $request->filled('sort_by') && $request->has('sort_type') && $request->filled('sort_type'), function ($query) use ($request) {
            $query->orderBy($request->sort_by ?? 'id', $request->sort_type ?? 'desc');
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
            $adj = AttendanceAdjustment::create([
                'attendance_id' => $request->attendance_id,
                'pic_id' => $request->pic_id,
                'user_id' => $user->id,
                'new_start_time' => $request->new_start_time,
                'new_end_time' => $request->new_end_time,
                'old_start_time' => $attendance->start_time,
                'old_end_time' => $attendance->end_time,
                'reason' => $request->reason
            ]);

            $this->createNotification($adj, $user, 'Perubahan Absen', 'Pengajuan perubahan absen dibuat oleh ' . $user->name);

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

        $operationalHour = OperationalHour::first();
        if (!$operationalHour) {
            return MessageDakama::warning("Operational hour not found!");
        }

        $user = UserSalary::where('user_id', $request->user_id)->first();
        if (!$user) {
            return MessageDakama::warning("User not registered of a salary!");
        }

        if ($adj->status == AttendanceAdjustment::STATUS_APPROVED && in_array($request->status, [AttendanceAdjustment::STATUS_REJECTED, AttendanceAdjustment::STATUS_WAITING])) {
            return MessageDakama::warning("Loan has been approved, can't be {$request->status}!");
        }

        try {
            if ($request->status == AttendanceAdjustment::STATUS_APPROVED) {
                $newStartTime = Carbon::parse($adj->new_start_time);

                $lateCut = 0;
                if (strtotime($newStartTime) > strtotime($operationalHour->late_time)) {
                    $lateCut = Helper::calculateLateCut($user->daily_salary, abs($newStartTime->diffInMinutes($operationalHour->late_time)));
                }

                $bonusOnTime = 0;
                if (strtotime($newStartTime) >= strtotime($operationalHour->ontime_start) && strtotime($newStartTime) <= strtotime($operationalHour->ontime_end)) {
                    $bonusOnTime = $operationalHour->bonus;
                }

                $adj->attendance()->update([
                    'start_time' => $adj->new_start_time,
                    'end_time' => $adj->new_end_time,
                    'late_cut' => $lateCut,
                    'bonus_ontime' => $bonusOnTime,
                    'late_minutes' => $lateCut != 0 ? abs($newStartTime->diffInMinutes($operationalHour->late_time)) : 0
                ]);
            }

            if ($request->status == AttendanceAdjustment::STATUS_CANCELLED) {
                $adj->attendance()->update([
                    'start_time' => $adj->old_start_time,
                    'end_time' => $adj->old_end_time,
                    'late_cut' => 0,
                    'bonus_ontime' => 0,
                    'late_minutes' => 0
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
