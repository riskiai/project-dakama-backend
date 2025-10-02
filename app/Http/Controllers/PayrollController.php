<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Resources\PayrollResource;
use App\Jobs\SendEmailApprovalJob;
use App\Models\Attendance;
use App\Models\MutationLoan;
use App\Models\OperationalHour;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

        $query->when($request->has('sort_by') && $request->filled('sort_by') && $request->has('sort_type') && $request->filled('sort_type'), function ($query) use ($request) {
            $query->orderBy($request->sort_by ?? 'id', $request->sort_type ?? 'desc');
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
            'bonus'         => 'required|numeric|min:0',
            'transport'         => 'required|numeric|min:0'
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

        $attendCount = Attendance::selectRaw("count(id) as total_working_day, sum(daily_salary) as total_salary, sum(late_cut) as total_late_cut, sum(duration) as total_duration, sum(makan) as total_makan")
            ->where('is_settled', 0)
            ->whereBetween('start_time', [$start, $end])
            ->where('type', 0)
            ->where('user_id', $userTarget->id)
            ->first();


        if ($attendCount->total_working_day < 1) {
            return MessageDakama::warning("User '{$userTarget->name}' doesn't have working day in {$start} to {$end}");
        }

        $overTimeCount = Attendance::selectRaw("sum(duration) as total_overtime_hour, sum(hourly_overtime_salary) as total_salary_overtime, sum(makan) as total_makan")
            ->where('is_settled', 0)
            ->whereBetween('start_time', [$start, $end])
            ->where('type', 1)
            ->where('user_id', $userTarget->id)
            ->first();

        $totalWorkingDay = $attendCount->total_working_day ?? 0;
        $totalHourAttend = $attendCount->total_duration ?? 0;
        $totalSalaryWorking = $attendCount->total_salary ?? 0;
        $totalLateCut = $attendCount->total_late_cut ?? 0;
        $totalSalaryOvertime = $overTimeCount->total_salary_overtime;
        $totalHourOvertime = $overTimeCount->total_overtime_hour ?? 0;
        $totalMakanAttend = $attendCount->total_makan ?? 0;
        $totakMakanOvertime = $overTimeCount->total_makan ?? 0;
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
            $payroll = Payroll::create([
                "user_id" => $userTarget->id,
                "pic_id" => $request->pic_id,
                "total_attendance" => $totalWorkingDay,
                "total_daily_salary" => $totalSalaryWorking,
                "total_overtime" => $totalSalaryOvertime,
                "total_late_cut" => $totalLateCut,
                "total_loan" => $totalLoan,
                "datetime" => "{$start}, {$end}",
                "notes" => $request->notes,
                "transport" => $request->transport,
                "bonus" => $request->bonus,
                "total_hour_attend" => $totalHourAttend,
                "total_hour_overtime" => $totalHourOvertime,
                "total_makan_attend" => $totalMakanAttend,
                "total_makan_overtime" => $totakMakanOvertime
            ]);

            Attendance::where('is_settled', 0)
                ->whereBetween('start_time', [$start, $end])
                ->update([
                    'is_settled' => 2 // pending
                ]);

            $this->createNotification($payroll, $user, 'Berita Gajian', "Berita gajian untuk {$userTarget->name} dari {$start} sampai {$end} telah dibuat oleh {$user->name}");

            DB::commit();
            return MessageDakama::success("Payroll successfully created", $payroll);
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

        if ($payroll->status == Payroll::STATUS_APPROVED && in_array($request->status, [Payroll::STATUS_REJECTED, Payroll::STATUS_WAITING])) {
            return MessageDakama::warning("Loan has been approved, can't be {$request->status}!");
        }

        $formData = [
            "status" => $request->status,
            'reason_approval' => $request->reason_approval,
        ];

        $dateTime = explode(", ", trim($payroll->datetime));

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

            if ($request->status == Payroll::STATUS_CANCELLED) {
                $payroll->mutations()->create([
                    'user_id' => $payroll->user_id,
                    'increase' => $payroll->total_loan,
                    'latest' => $payroll->user->loan,
                    'total' => $payroll->user->loan + $payroll->total_loan,
                    'description' => "Loan {$payroll->total_loan} cancelled by {$user->name}",
                    'created_by' => $payroll->user_id
                ]);

                $payroll->user()->update([
                    'loan' => $payroll->user->loan + $payroll->total_loan
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

    public function getDocument($id, $type)
    {
        if (!in_array($type, ['preview', 'download'])) {
            abort(404);
        }

        $payroll = Payroll::with(['user.role', 'user.salary', 'user.divisi'])->find($id);
        // dd($payroll->toArray());
        if (!$payroll) {
            abort(404);
        }

        if ($payroll->status != Payroll::STATUS_APPROVED) {
            abort(404);
        }

        $operationalHour = OperationalHour::first();

        $dateTime = explode(', ', $payroll->datetime);
        $attendances = Attendance::with(['project.company.contactType', 'budget', 'overtime'])
            ->where('type', 0)
            ->where('user_id', $payroll->user_id)
            ->whereBetween('start_time', $dateTime)
            ->get()
            ->keyBy(function ($row) {
                return Carbon::parse($row->start_time)->format('Y-m-d');
            });
        $overtimes = Attendance::with(['project.company.contactType', 'budget', 'overtime'])
            ->where('type', 1)
            ->where('user_id', $payroll->user_id)
            ->whereBetween('start_time', $dateTime)
            ->get()
            ->keyBy(function ($row) {
                return Carbon::parse($row->start_time)->format('Y-m-d');
            });

        $slip = [
            "company_name" => config('app.name'),
            "target_name" =>  $payroll->user->name,
            "target_poss" => $payroll->user->divisi->name,
            "payout_account" => "{$payroll->user->bank_name} - {$payroll->user->account_number}",
            "range_date" => $payroll->datetime,
            "last_project" => $attendances->last()->project->name ?? null,
            "last_placement" => $attendances->last()->budget->nama_budget ?? null,
            "attendances" => $attendances,
            "overtimes" => $overtimes,
            "reports" => [
                [
                    "label" => "JHK",
                    "amount" => "{$payroll->total_hour_attend} Jam",
                    "rate" => $payroll->user->salary->hourly_salary,
                    "total" => $payroll->total_daily_salary,
                ],
                [
                    "label" => "JJL",
                    "amount" => "{$payroll->total_hour_overtime} Jam",
                    "rate" => $payroll->user->salary->hourly_overtime_salary,
                    "total" => $payroll->total_overtime,
                ],
                [
                    "label" => "Makan",
                    "amount" => $payroll->total_attendance + collect($overtimes)->where("makan", "!=", "0")->count() . " Hr",
                    "rate" => $payroll->user->salary->makan,
                    "total" => $attendances->sum('makan') + $overtimes->sum('makan'),
                ],
                // [
                //     "label" => "Total Bonus",
                //     "amount" => "{$payroll->total_attendance} Hr",
                //     "rate" => $operationalHour->bonus,
                //     "total" => $attendances->where('bonus_ontime', '>', 0)->where('type', 0)->sum('bonus_ontime'),
                // ],
            ],
            "report_others" => [
                [
                    "label" => "Bonus",
                    "total" => $payroll->bonus
                ],
                [
                    "label" => "Transport",
                    "total" => $payroll->transport
                ],
            ],
            "bonus_jhk" => $attendances->where('bonus_ontime', '>', 0)->where('type', 0)->count(),
            "bonus" => $attendances->where('bonus_ontime', '>', 0)->where('type', 0)->sum('bonus_ontime'),
            "kasbon" => $payroll->total_loan,
        ];

        // dd($slip);

        $html = view('payroll.document.slip', [
            "slip" => $slip,
        ])->render();

        // return $html;

        $pdf = PDF::loadHTML($html)->setPaper('A4', 'landscape')->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);

        $pdf->render();


        if ($type == 'preview') {
            return $pdf->stream();
        }

        $date = Carbon::now()->format('YmdHis');
        return $pdf->download("{$date}-payroll-" . Str::slug($payroll->user->name, "_") . ".pdf");
    }
}
