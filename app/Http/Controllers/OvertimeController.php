<?php

namespace App\Http\Controllers;

use App\Facades\Helper;
use App\Facades\MessageDakama;
use App\Http\Resources\Overtime\OvertimeCollection;
use App\Http\Resources\Overtime\OvertimeResource;
use App\Models\Overtime;
use App\Models\Role;
use App\Models\UserProjectAbsen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Overtime::query();

        $query->with(['project', 'task' => function ($query) {
            $query->withTrashed();
        }, 'user']);

        if ($user->hasRole(Role::KARYAWAN)) {
            $query->where('user_id', $user->id);
        }

        $query->when($request->has('user_id') && $request->filled('user_id'), function ($query) use ($request) {
            $query->where('user_id', $request->user_id);
        });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->when($request->has('project_id') && $request->filled('project_id'), function ($query) use ($request) {
            $query->where('project_id', $request->project_id);
        });

        $query->when($request->has('task_id') && $request->filled('task_id'), function ($query) use ($request) {
            $query->where('task_id', $request->task_id);
        });

        $query->when($request->has('start_date') && $request->filled('start_date') &&
            $request->has('end_date') && $request->filled('end_date'), function ($query) use ($request) {

            $query->whereBetween('request_date', [$request->start_date, $request->end_date]);
        });

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $overtimes = $query->paginate($request->per_page);
        } else {
            $overtimes = $query->get();
        }


        return new OvertimeCollection($overtimes);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'project_id'    => 'required|exists:projects,id',
            'task_id'       => 'required|exists:tasks,id',
            'request_date'  => 'required|date_format:Y-m-d',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'reason'        => 'max:255',
            'pic_id'        => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $overtime = Overtime::where('user_id', $user->id)
            ->whereDate('request_date', Carbon::createFromFormat('Y-m-d', $request->request_date)->toDateTime())
            ->where('project_id', $request->project_id)
            ->where('task_id', $request->task_id)
            ->first();
        if ($overtime) {
            return MessageDakama::warning('Overtime already exist');
        }

        try {
            Overtime::create([
                'project_id' => $request->project_id,
                'task_id' => $request->task_id,
                'request_date' => $request->request_date,
                'reason' => $request->reason ?? "-",
                'user_id' => $user->id,
                'pic_id' => $request->pic_id,
                'salary_overtime' => 0,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => Helper::calculateDurationTime($request->start_time, $request->end_time),
            ]);

            DB::commit();
            return MessageDakama::success('Overtime successfully created');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function show($id)
    {
        $overtime = Overtime::with(['task' => function ($query) {
            $query->withTrashed();
        }])->find($id);
        if (!$overtime) {
            return MessageDakama::notFound('Overtime not found');
        }

        $overtime->load(['project', 'user']);

        return new OvertimeResource($overtime);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $overtime = Overtime::find($id);
        if (!$overtime) {
            return MessageDakama::notFound('Overtime not found');
        }

        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'task_id' => 'required|exists:tasks,id',
            'request_date' => 'required|date_format:Y-m-d H:i:s',
            'reason' => 'max:255',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $overtime->update([
                'project_id' => $request->project_id,
                'task_id' => $request->task_id,
                'request_date' => $request->request_date,
                'reason' => $request->reason ?? "-",
            ]);

            DB::commit();
            return MessageDakama::success('Overtime successfully updated');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function approval(Request $request, $id)
    {
        DB::beginTransaction();

        $overtime = Overtime::with(['user.salary'])->find($id);
        if (!$overtime) {
            return MessageDakama::notFound('Overtime not found');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,cancelled',
            'reason_approval' => 'nullable',
            'is_overtime_meal' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$overtime->user->salary) {
            return MessageDakama::warning("User not registered of a salary!");
        }

        if (in_array($overtime->status, [Overtime::STATUS_APPROVED, Overtime::STATUS_REJECTED, Overtime::STATUS_CANCELLED])) {
            return MessageDakama::warning("Overtime has been {$overtime->status}, can't be processed!");
        }

        try {
            $formData = [
                'status' => $request->status,
                'reason_approval' => $request->reason_approval
            ];

            if ($request->status == Overtime::STATUS_APPROVED) {
                $formData['salary_overtime'] = $overtime->user->salary->hourly_overtime_salary;

                if ($request->is_overtime_meal == true) {
                    $formData['makan'] = $request->has('makan') ? $request->makan : $overtime->user->salary->makan ?? 0;
                }
            }

            $overtime->update($formData);

            DB::commit();
            return MessageDakama::success("Overtime successfully {$request->status}", $overtime);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        $overtime = Overtime::find($id);
        if (!$overtime) {
            return MessageDakama::notFound('Overtime not found');
        }

        if ($overtime->status != Overtime::STATUS_WAITING) {
            return MessageDakama::warning("Overtime has been {$overtime->status}, can't delete!");
        }

        try {
            $overtime->delete();

            DB::commit();
            return MessageDakama::success('Overtime successfully deleted');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
