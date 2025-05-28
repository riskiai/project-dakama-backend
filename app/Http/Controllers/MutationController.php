<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Resources\Mutation\LoanResource;
use App\Models\MutationLoan;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MutationController extends Controller
{
    public function getLoan(Request $request)
    {
        $user = Auth::user();

        $query = MutationLoan::query();

        $query->with(['pic', 'user']);

        if ($user->hasRole(Role::KARYAWAN)) {
            $query->where('user_id', $user->id);
        }

        $query->when($request->has('loan_id') && $request->filled('loan_id'), function ($query) use ($request) {
            $query->where('mutable_id', $request->loan_id);
        });

        $query->orderBy('id', 'desc');

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $mutations = $query->paginate($request->per_page);
        } else {
            $mutations = $query->get();
        }

        return LoanResource::collection($mutations);
    }
}
