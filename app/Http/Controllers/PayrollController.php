<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Reference : OnvertimeController, AttendanceController, LoanController
class PayrollController extends Controller
{
    public function index()
    {
        // query to payroll table
        // filter if user access role is 'karyawan', data will be filtered by user_id. but if user access role is not 'karyawan', data show all

        // check if paginate true, format response is paginate but if paginate false, format response is collection/get

        // return new PayrollResource::collection($payrolls);
    }

    public function store(Request $request)
    {

        // DB::beginTransaction();

        $user = Auth::user();

        // $validator = Validator::make($request->all(), [
        //     'user_id'       => 'required|exists:users,id',
        //     'total_loan'    => 'required|integer',
        //     'start_date'    => 'required|date',
        //     'end_date'      => 'required|date',
        //     'notes'         => 'nullable|string'
        //     // 'is_all_loan'   => 'required|date',
        //     // 'loan'          => 'required|date',
        //     // 'total_attendance'      => 'required|integer',
        //     // 'pic_id'                => 'required|exists:users,id',
        // ]);

        // if ($validator->fails()) {
        //     return MessageDakama::render([
        //         'status' => MessageDakama::WARNING,
        //         'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
        //         'message' => $validator->errors()
        //     ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        // }

        if ($user->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning('You are not allowed to process payrolls');
        }

        $start = $request->start_date;
        $end = $request->end_date;

        $Attendance = Attendance::where('user_id', $user->id)->whereBetween('start_time', [$start, $end])->get();

        $overTime = Overtime::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->orWhereBetween('request_date', [$start, $end])->get();

        dd($Attendance, $overTime);


        // validation : user_id, start_date, end_date, loan, is_all_loan (boolean), notes (opsional)

        // check user not 'karyawan' role

        // query to attendance table by user_id, start_date, end_date of start_time

        // query to overtime table by user_id, start_date, end_date

        // calculate total working working day
        // calculate total salary working day
        // calculate total late cut
        // calculate total salary overtime

        // check the loan user table column to see if the nominal amount matches the amount recorded or does not exceed the amount recorded.
        // calculate the total amount of loan, if is_all_loan is true, then the total amount of loan is the total amount of loan recorded in the users loan table. but if is_all_loan is false, then the total loan amount is the loan amount that has been input.

        // create payroll
    }

    public function show($id)
    {
        // query to payroll table by id
        // check if not exists return 404

        // return new PayrollResource($payroll);
    }

    public function update(Request $request, $id)
    {
        // update allow update if status is 'waiting'
        // update action same with create process
    }

    public function approval(Request $request, $id)
    {
        // query to payroll table by id
        // check if not exists return 404

        // validation : status = 'approved|rejected|cancelled'

        // check status is approved or other, if status is 'approved', continue the update process below (#1 and #2). but if status is not 'approved', update status payroll to status request (#1 only)

        // #1
        // update status to requested and set approved at`

        // #2
        // update attendance is_settled to true by user_id and date between start_date and end_date
        // update the loan user table column. reduce the existing data with the data from the total loan
        // create mutation loan table
        /** example mutation :
         * MutationLoan::create([
         * 'user_id' => $payroll->user_id,
         * 'decrease' => $payroll->total_loan,
         * 'latest' => $payroll->user->loan,
         * 'total' => $payroll->user->loan - $payroll->total_loan,
         * 'description' => "Loan {$payroll->total_loan} approved by {$pic->name}",
         * 'created_by' => $payroll->user_id
         * ]);
         *
         * $payroll->user()->update([
         * 'loan' => $payroll->user->loan - $payroll->total_loan
         * ]);
         */
    }

    public function destroy($id)
    {
        // delete before status approval is approved
    }
}
