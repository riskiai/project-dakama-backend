<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Users\UsersCollection;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan parameter 'search'
        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('role', function ($query) use ($request) {
                        $query->where('role_name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('divisi', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter berdasarkan 'divisi_name' secara spesifik
        if ($request->has('divisi_name')) {
            $query->whereHas('divisi', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->divisi_name . '%');
            });
        }

        if ($request->has('role_id')) {
            // Ambil array role_id dari request, pastikan dalam bentuk array
            $roleIds = is_array($request->role_id) ? $request->role_id : explode(',', $request->role_id);
    
            // Terapkan filter berdasarkan role_id
            $query->whereIn('role_id', $roleIds);
        }

        // Filter berdasarkan rentang tanggal (parameter 'date')
        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);
            $query->whereBetween('created_at', $date);
        }

        $users = $query->paginate($request->per_page);

        return new UsersCollection($users);
    }
}
