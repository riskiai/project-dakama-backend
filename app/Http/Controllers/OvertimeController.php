<?php

namespace App\Http\Controllers;

use App\Facades\Helper;
use App\Facades\MessageDakama;
use App\Http\Resources\Overtime\OvertimeCollection;
use App\Http\Resources\Overtime\OvertimeResource;
use App\Jobs\SendEmailApprovalJob;
use App\Models\Attendance;
use App\Models\OperationalHour;
use App\Models\Overtime;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
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

        $query->with(['project', 'budget' => function ($query) {
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

        $query->when($request->has('budget_id') && $request->filled('budget_id'), function ($query) use ($request) {
            $query->where('budget_id', $request->budget_id);
        });

        $query->when($request->has('start_date') && $request->filled('start_date') &&
            $request->has('end_date') && $request->filled('end_date'), function ($query) use ($request) {

            $query->whereBetween('request_date', [$request->start_date, $request->end_date]);
        });

        $query->when($request->has('sort_by') && $request->filled('sort_by') && $request->has('sort_type') && $request->filled('sort_type'), function ($query) use ($request) {
            $query->orderBy($request->sort_by ?? 'id', $request->sort_type ?? 'desc');
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

        $validator = Validator::make($request->all(), [
            'project_id'    => 'required|exists:projects,id',
            'budget_id'     => 'required|exists:budgets,id',
            'request_date'  => 'required|date_format:Y-m-d',
            'reason'        => 'max:255',
            'user_id'        => 'required|exists:users,id',
            'is_allow_meal'         => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::find($request->user_id);

        $operationalHour = Project::find($request->project_id)->operationalHour;
        if (!$operationalHour) {
            return MessageDakama::warning("Operational hour not found!");
        }

        $overtime = Overtime::where('user_id', $user->id)
            ->whereDate('request_date', Carbon::createFromFormat('Y-m-d', $request->request_date)->toDateTime())
            ->where('project_id', $request->project_id)
            ->where('budget_id', $request->budget_id)
            ->first();
        if ($overtime) {
            return MessageDakama::warning('Overtime already exist');
        }

        try {
            $overtime = Overtime::create([
                'project_id' => $request->project_id,
                'budget_id' => $request->budget_id,
                'request_date' => $request->request_date,
                'reason' => $request->reason ?? "-",
                'user_id' => $user->id,
                'pic_id' => $request->pic_id,
                'salary_overtime' => 0,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => Overtime::STATUS_APPROVED,
                'makan' => $request->is_allow_meal == true ? $user->salary->makan : 0
            ]);

            $overtime->attendance()->create([
                'user_id' => $user->id,
                'budget_id' => $request->budget_id,
                'project_id' => $request->project_id,
                'start_time' => Carbon::parse($request->request_date . ' ' . $operationalHour->offtime),
                'type' => 1,
                'image_in' => "-",
                'duration' => 0,
                'makan' => $request->is_allow_meal == true ? $user->salary->makan : 0
            ]);

            // $this->createNotification($overtime, $user, 'Permintaan Lembur', 'Permintaan lembur dari ' . $user->name);

            DB::commit();
            return MessageDakama::success('Overtime successfully created');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function show($id)
    {
        $overtime = Overtime::with(['budget' => function ($query) {
            $query->withTrashed();
        }])->find($id);
        if (!$overtime) {
            return MessageDakama::notFound('Overtime not found');
        }

        $overtime->load(['project', 'user']);

        return new OvertimeResource($overtime);
    }

    public function showCurrent()
    {
        $user = Auth::user();

        $overtime = Overtime::with(['budget' => function ($query) {
            $query->withTrashed();
        }])->where('user_id', $user->id)
            ->whereDate('request_date', now())
            ->where('status', Overtime::STATUS_APPROVED)
            ->first();
        if (!$overtime) {
            return MessageDakama::notFound('Overtime not found');
        }

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
            'budget_id' => 'required|exists:budgets,id',
            'request_date' => 'required|date_format:Y-m-d',
            'reason' => 'max:255',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $operationalHour = OperationalHour::first();
        if (!$operationalHour) {
            return MessageDakama::warning("Operational hour not found!");
        }

        try {
            $overtime->update([
                'project_id' => $request->project_id,
                'budget_id' => $request->budget_id,
                'pic_id' => $request->pic_id,
                'request_date' => $request->request_date,
                'reason' => $request->reason ?? "-",
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);

            $overtime->attendance()->update([
                'user_id' => $overtime->user->id,
                'budget_id' => $request->budget_id,
                'project_id' => $request->project_id,
                'start_time' => Carbon::parse($request->request_date . ' ' . $operationalHour->offtime)->format('Y-m-d H:i:s'),
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
        abort(404);

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

        $user = Auth::user();

        if ($user->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning("You can't process Overtime!");
        }

        if (!$overtime->user->salary) {
            return MessageDakama::warning("User not registered of a salary!");
        }

        if ($overtime->status == Overtime::STATUS_APPROVED && in_array($request->status, [Overtime::STATUS_REJECTED, Overtime::STATUS_WAITING])) {
            return MessageDakama::warning("Loan has been approved, can't be {$request->status}!");
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

            if ($request->status == Overtime::STATUS_CANCELLED) {
                $formData['salary_overtime'] = 0;
                $formData['makan'] = 0;
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

        try {
            $overtime->delete();

            $overtime->attendance->delete();

            DB::commit();
            return MessageDakama::success('Overtime successfully deleted');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
