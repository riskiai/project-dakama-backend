<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Resources\PayrollResource;
use App\Models\Attendance;
use App\Models\MutationLoan;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Reference : OnvertimeController, AttendanceController, LoanController
class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Payroll::query();

        $query->with(['pic', 'user']);

        if ($user->hasRole(Role::KARYAWAN)) {
            $query->where('user_id', $user->id);
            $query->where('status', Payroll::STATUS_APPROVED);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('pic_id')) {
            $query->where('pic_id', $request->pic_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approved_by')) {
            $query->where('approved_by', $request->approved_by);
        }

        $query->when($request->has('start_date') && $request->filled('start_date') &&
            $request->has('end_date') && $request->filled('end_date'), function ($query) use ($request) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        });

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $payrolls = $query->paginate($request->per_page);
        } else {
            $payrolls = $query->get();
        }

        return PayrollResource::collection($payrolls);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'user_id'       => 'required|exists:users,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'notes'         => 'nullable|string',
            'is_all_loan'   => 'required|boolean',
            'loan'          => 'required_if:is_all_loan,false|nullable|integer',
            'notes'         => 'max:255',
            'pic_id'        => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning('You are not allowed to process payrolls');
        }

        $start = $request->start_date;
        $end = $request->end_date;
        $userTarget = User::find($request->user_id);

        $attendCount = Attendance::selectRaw("count(id) as total_working_day, sum(daily_salary + makan + transport + bonus_ontime) as total_salary, sum(late_cut) as total_late_cut")
            ->where('is_settled', 0)
            ->whereBetween('start_time', [$start, $end])
            ->first();

        $overTimeCount = Overtime::selectRaw("sum(duration) as total_overtime_hour, sum(salary_overtime) as total_salary_overtime")
            ->whereBetween('request_date', [$start, $end])
            ->where('status', Overtime::STATUS_APPROVED)
            ->first();

        $totalWorkingDay = $attendCount->total_working_day ?? 0;
        $totalSalaryWorking = $attendCount->total_salary ?? 0;
        $totalLateCut = $attendCount->total_late_cut ?? 0;
        $totalSalaryOvertime = $overTimeCount->total_salary_overtime ?? 0;
        $totalLoan = 0;

        if ($request->is_all_loan == true) {
            $totalLoan = $userTarget->loan;
        } else {
            $totalLoan = $request->loan;
        }

        if ($totalLoan > $userTarget->loan) {
            return  MessageDakama::warning("total loan exceeds user loan balance");
        }

        if ($totalLoan < 0) {
            return MessageDakama::warning("total loan cannot under zero");
        }

        try {
            Payroll::create([
                "user_id" => $userTarget->id,
                "pic_id" => $request->pic_id,
                "total_attendance" => $totalWorkingDay,
                "total_daily_salary" => $totalSalaryWorking,
                "total_overtime" => $totalSalaryOvertime,
                "total_late_cut" => $totalLateCut,
                "total_loan" => $totalLoan,
                "datetime" => "{$start}, {$end}",
                "notes" => $request->notes,
            ]);

            DB::commit();
            return MessageDakama::success("Payroll successfully created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function show($id)
    {
        $payroll = Payroll::find($id);
        if (!$payroll) {
            return MessageDakama::notFound("Payroll not found");
        }

        $payroll->load(['pic', 'user']);

        return new PayrollResource($payroll);
    }

    public function approval(Request $request, $id)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $payroll = Payroll::with(['pic', 'user'])->find($id);
        if (!$payroll) {
            return MessageDakama::notFound("Payroll not found");
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,cancelled',
            'reason_approval' => 'nullable'
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning('You are not allowed to process payrolls');
        }

        if ($payroll->status == Payroll::STATUS_APPROVED) {
            return MessageDakama::warning("Payroll has been approved, can't be processed!");
        }

        $formData = [
            "status" => $request->status,
            'reason_approval' => $request->reason_approval,
        ];

        $dateTime = explode(",", trim($payroll->datetime));

        $dateTime = array_map('trim', $dateTime);

        try {
            if ($request->status == Payroll::STATUS_APPROVED) {
                Attendance::whereBetween('created_at', $dateTime)->where('is_settled', 0)->update([
                    'is_settled' => 1
                ]);

                $formData['approved_at'] = now();
                $formData['approved_by'] = $user->id;

                $payroll->mutations()->create([
                    'user_id' => $payroll->user_id,
                    'decrease' => $payroll->total_loan,
                    'latest' => $payroll->user->loan,
                    'total' => $payroll->user->loan - $payroll->total_loan,
                    'description' => "Loan {$payroll->total_loan} approved by {$user->name}",
                    'created_by' => $payroll->user_id
                ]);

                $payroll->user()->update([
                    'loan' => $payroll->user->loan - $payroll->total_loan
                ]);
            }

            $payroll->update($formData);

            DB::commit();
            return MessageDakama::success("Payroll successfully processed");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        $payroll = Payroll::find($id);
        if (!$payroll) {
            return MessageDakama::notFound('Payroll not found');
        }

        if ($payroll->status == Payroll::STATUS_APPROVED) {
            return MessageDakama::warning("Payroll has been approved, can't delete!");
        }

        try {
            $payroll->delete();

            DB::commit();
            return MessageDakama::success("Payroll successfully deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function counting(Request $request)
    {
        $payroll = Payroll::selectRaw('sum(total_daily_salary) as total_daily_salary, sum(total_loan) as total_loan, sum(total_late_cut) as total_late_cut, sum(total_overtime) as total_overtime')
            ->when($request->filled('user_id') && $request->has('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            })
            ->when($request->filled('pic_id') && $request->has('pic_id'), function ($query) use ($request) {
                $query->where('pic_id', $request->pic_id);
            })
            ->when($request->filled('status') && $request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('date') && $request->has('date'), function ($query) use ($request) {
                $query->whereDate('approved_at', $request->date);
            })
            ->first();

        return MessageDakama::success("Payroll successfully counted", [
            "total_daily_salary" => (int) $payroll->total_daily_salary,
            "total_loan" => (int) $payroll->total_loan,
            "total_late_cut" => (int) $payroll->total_late_cut,
            "total_overtime" => (int) $payroll->total_overtime,
            "total_salary" => ($payroll->total_daily_salary + $payroll->total_loan - $payroll->total_late_cut + $payroll->total_overtime),
        ]);
    }
}
