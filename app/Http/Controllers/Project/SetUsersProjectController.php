<?php

namespace App\Http\Controllers\Project;

use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use App\Models\UserProjectAbsen;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\SetAbsen\CreateRequest;
use App\Http\Requests\Project\SetAbsen\UpdateRequest;
use App\Http\Resources\Project\SetUserProjectAbsenCollection;
use App\Http\Resources\Project\SetShowUserProjectAbsenCollection;

class SetUsersProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = UserProjectAbsen::query();

        // 🔍 Filtering opsional
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderByDesc('jam_masuk');

        $absensi = $query->paginate($request->per_page);

        return new SetUserProjectAbsenCollection($absensi);
    }

    public function indexAll(Request $request) {
        $query = UserProjectAbsen::query();

        // 🔍 Filtering opsional
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderByDesc('jam_masuk');

        $absensi = $query->get();

        return new SetUserProjectAbsenCollection($absensi);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $userIds = array_filter($request->input('user_id', []));

            foreach ($userIds as $userId) {
                UserProjectAbsen::create([
                    'user_id'    => $userId,
                    'project_id' => $request->project_id,
                    'longitude'  => $request->longitude,
                    'latitude'   => $request->latitude,
                    'radius'     => $request->radius,
                    'status'     => null, // status absen default null (belum absen)
                    'jam_masuk'  => null,
                    'jam_pulang' => null,
                    'keterangan' => null,
                ]);
            }

            DB::commit();
            return MessageDakama::success('Pendaftaran absensi berhasil dibuat untuk ' . count($userIds) . ' pengguna.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal mendaftarkan absen: " . $th->getMessage());
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $absen = UserProjectAbsen::find($id);

            if (!$absen) {
                return MessageDakama::notFound("Data absen dengan ID $id tidak ditemukan.");
            }

            $absen->update([
                'user_id'    => $request->user_id,
                'project_id' => $request->project_id,
                'longitude'  => $request->longitude,
                'latitude'   => $request->latitude,
                'radius'     => $request->radius,
            ]);

            DB::commit();
            return MessageDakama::success("Data absen berhasil diperbarui.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal mengupdate absen: " . $th->getMessage());
        }
    }

    public function show($id)
    {
        $absen = UserProjectAbsen::with(['user', 'project'])->find($id);

        if (!$absen) {
            return response()->json([
                'success' => false,
                'message' => "Data absen dengan ID $id tidak ditemukan.",
            ], 404);
        }

        return new SetShowUserProjectAbsenCollection($absen);
    }

    public function delete(string $id)
    {
        DB::beginTransaction();

        $absen = UserProjectAbsen::find($id);

        if (!$absen) {
            return MessageDakama::notFound('Data absen tidak ditemukan.');
        }

        try {
            $absen->delete();

            DB::commit();
            return MessageDakama::success("Data absen ID $id berhasil dihapus.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal menghapus data absen: " . $th->getMessage());
        }
    }

}
