<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Resources\Loan\LoanCollection;
use App\Http\Resources\Loan\LoanResource;
use App\Models\EmployeeLoan;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = EmployeeLoan::query();

        $query->with(['pic', 'user']);

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
            $query->whereDate('request_date', $request->date);
        });

        $query->when($request->has('user_id') && $request->filled('user_id'), function ($query) use ($request) {
            $query->where('user_id', $request->user_id);
        });

        // $query->when($request->has('user_id') && $request->filled('user_id'), function ($query) use ($request) {
        //     $query->whereHas('mutations', function ($query) use ($request) {
        //         $query->where('user_id', $request->user_id);
        //     });
        // });

        $query->when($request->has('start_date') && $request->filled('start_date') &&
            $request->has('end_date') && $request->filled('end_date'), function ($query) use ($request) {
            $query->whereBetween('request_date', [$request->start_date, $request->end_date]);
        });

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $loans = $query->paginate($request->per_page);
        } else {
            $loans = $query->get();
        }

        return new LoanCollection($loans);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nominal' => 'required|numeric|integer',
            'request_date' => 'required|date_format:Y-m-d',
            'reason' => 'required|max:255',
            'pic_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            EmployeeLoan::create([
                'user_id' => $user->id,
                'nominal' => $request->nominal,
                'request_date' => $request->request_date,
                'reason' => $request->reason,
                'latest' => $request->nominal,
                'pic_id' => $request->pic_id
            ]);

            DB::commit();
            return MessageDakama::success('Loan successfully created');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function show($id)
    {
        $loan = EmployeeLoan::find($id);
        if (!$loan) {
            return MessageDakama::notFound('Loan not found');
        }

        $loan->load(['pic', 'user']);

        return new LoanResource($loan);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $loan = EmployeeLoan::find($id);
        if (!$loan) {
            return MessageDakama::notFound('Loan not found');
        }

        $validator = Validator::make($request->all(), [
            'nominal' => 'required|numeric|integer',
            'request_date' => 'required|date_format:Y-m-d',
            'reason' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($loan->status != EmployeeLoan::STATUS_WAITING) {
            return MessageDakama::warning("Loan has been {$loan->status}, can't be processed!");
        }

        try {
            $loan->update([
                'nominal' => $request->nominal,
                'request_date' => $request->request_date,
                'reason' => $request->reason
            ]);

            DB::commit();
            return MessageDakama::success('Loan successfully updated');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function approval(Request $request, $id)
    {
        DB::beginTransaction();

        $user = Auth::user();

        $loan = EmployeeLoan::find($id);
        if (!$loan) {
            return MessageDakama::notFound('Loan not found');
        }

        if ($user->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning('You are not allowed to process loan');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,cancelled',
            'reason_approval' => 'nullable',
            'pic_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($loan->status != EmployeeLoan::STATUS_WAITING) {
            return MessageDakama::warning("Loan has been {$loan->status}, can't be processed!");
        }

        if (in_array($loan->status, [EmployeeLoan::STATUS_APPROVED, EmployeeLoan::STATUS_REJECTED, EmployeeLoan::STATUS_CANCELLED])) {
            return MessageDakama::warning("Overtime has been {$loan->status}, can't be processed!");
        }

        $loan->load(['pic', 'user']);

        try {
            $formData = [
                'status' => $request->status,
                'reason_approval' => $request->reason_approval,
                'pic_id' => $request->pic_id
            ];

            if ($request->status == EmployeeLoan::STATUS_APPROVED) {
                $loan->mutations()->create([
                    'user_id' => $loan->user_id,
                    'increase' => $loan->nominal,
                    'latest' => $loan->user->loan,
                    'total' => $loan->user->loan + $loan->nominal,
                    'description' => "Loan {$loan->nominal} approved by {$user->name}",
                    'created_by' => $user->id
                ]);

                $loan->user()->update([
                    'loan' => $loan->user->loan + $loan->nominal
                ]);
            }

            $loan->update($formData);

            DB::commit();
            return MessageDakama::success("Loan successfully {$request->status}");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function payment(Request $request, $id)
    {
        DB::beginTransaction();

        $loan = EmployeeLoan::find($id);
        if (!$loan) {
            return MessageDakama::notFound('Loan not found');
        }

        $user = Auth::user();

        if ($user->hasRole(Role::KARYAWAN)) {
            return MessageDakama::warning('You are not allowed to process loan');
        }

        $validator = Validator::make($request->all(), [
            'nominal' => 'required|numeric|integer',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($loan->status != EmployeeLoan::STATUS_APPROVED) {
            return MessageDakama::warning("Loan has been {$loan->status}, can't be processed!");
        }

        if ($request->nominal > $loan->latest) {
            return MessageDakama::warning("Nominal can't be bigger than loan balance {$loan->latest}, can't be processed!");
        }

        if ($request->nominal < 1) {
            return MessageDakama::warning("Nominal can't be less than 1, can't be processed!");
        }

        if ($loan->is_settled) {
            return MessageDakama::warning("Loan has been settled, can't be processed!");
        }

        try {
            $loan->mutations()->create([
                'user_id' => $loan->user_id,
                'decrease' => $request->nominal,
                'latest' => $loan->user->loan,
                'total' => $loan->user->loan - $request->nominal,
                'description' => "Loan payment {$request->nominal} by {$loan->user->name}",
                'created_by' => $user->id
            ]);

            $loan->user()->update([
                'loan' => $loan->user->loan - $request->nominal
            ]);

            $loan->update([
                'latest' => ($loan->latest) - $request->nominal,
                'is_settled' => ($loan->latest) - $request->nominal < 1 ? true : false
            ]);

            DB::commit();
            return MessageDakama::success("Loan payment successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        $loan = EmployeeLoan::find($id);
        if (!$loan) {
            return MessageDakama::notFound('Loan not found');
        }

        if ($loan->status != EmployeeLoan::STATUS_WAITING) {
            return MessageDakama::warning("Loan has been {$loan->status}, can't delete!");
        }

        try {
            $loan->delete();

            DB::commit();
            return MessageDakama::success('Loan successfully deleted');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
