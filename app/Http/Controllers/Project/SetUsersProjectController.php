<?php

namespace App\Http\Controllers\Project;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use App\Models\UserProjectAbsen;
use App\Models\ProjectHasLocation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\SetAbsen\CreateRequest;
use App\Http\Requests\Project\SetAbsen\UpdateRequest;
use App\Http\Requests\Project\SetAbsen\BulkUpdateRequest;
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

        $absensi = $query->paginate($request->per_page);

        return new SetUserProjectAbsenCollection($absensi);
    }

    public function indexAll(Request $request)
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

        $absensi = $query->get();

        return new SetUserProjectAbsenCollection($absensi);
    }

    /* public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $userIds = array_filter($request->input('user_id', []));

            if (!$request->has('location_id')) {
                $location = ProjectHasLocation::where('project_id', $request->project_id)->first();

                if (!$location) {
                    return MessageDakama::warning('Project tidak memiliki lokasi');
                }

                $request->merge(['location_id' => $location->id]);
            }

            foreach ($userIds as $userId) {
                UserProjectAbsen::create([
                    'user_id'    => $userId,
                    'project_id' => $request->project_id,
                    'location_id' => $request->location_id
                ]);
            }

            DB::commit();
            return MessageDakama::success('Pendaftaran absensi berhasil dibuat untuk ' . count($userIds) . ' pengguna.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal mendaftarkan absen: " . $th->getMessage());
        }
    } */

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $userIds = array_map('intval', array_filter($request->input('user_id', [])));

            // Tentukan location_id (default jika kosong)
            $locationId = $request->input('location_id');
            if (!$locationId) {
                $location = ProjectHasLocation::where('project_id', $request->project_id)->first();
                if (!$location) {
                    return MessageDakama::warning('Project tidak memiliki lokasi');
                }
                $locationId = $location->id;
            }

            // Cek lagi siapa yang sudah terdaftar (agar message lebih informatif)
            $already = UserProjectAbsen::where('project_id', $request->project_id)
                        ->whereNull('deleted_at')
                        ->whereIn('user_id', $userIds)
                        ->pluck('user_id')
                        ->toArray();

            if (!empty($already)) {
                DB::rollBack();
                return MessageDakama::warning('Beberapa user sudah terdaftar pada project ini: [' . implode(', ', $already) . '].');
            }

            foreach ($userIds as $userId) {
                UserProjectAbsen::create([
                    'user_id'     => $userId,
                    'project_id'  => $request->project_id,
                    'location_id' => $locationId,
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

            if (!$request->has('location_id')) {
                $location = ProjectHasLocation::where('project_id', $request->project_id)->first();

                if (!$location) {
                    return MessageDakama::warning('Project tidak memiliki lokasi');
                }

                $request->merge(['location_id' => $location->id]);
            }

            $absen->update([
                'user_id'    => $request->user_id,
                'project_id' => $request->project_id,
                'location_id' => $request->location_id
            ]);

            DB::commit();
            return MessageDakama::success("Data absen berhasil diperbarui.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal mengupdate absen: " . $th->getMessage());
        }
    }

     public function bulkUpdate(BulkUpdateRequest $request, string $project)
    {
        $projectId = (string) $project; // contoh: "PRO-25-013"

        // Pastikan project ada
        if (!Project::where('id', $projectId)->exists()) {
            return MessageDakama::notFound("Project {$projectId} tidak ditemukan.");
        }

        $payload = collect($request->input('users_detail', []))
                    ->map(fn($row) => [
                        'user_id'     => (int) $row['user_id'],
                        'location_id' => $row['location_id'] ?? null,
                    ]);

        DB::beginTransaction();

        try {
            // Default lokasi (jika ada item yg location_id = null)
            $defaultLoc = ProjectHasLocation::where('project_id', $projectId)->first();

            // Normalisasi location_id null -> default
            $payload = $payload->map(function ($row) use ($defaultLoc, $projectId) {
                if (empty($row['location_id'])) {
                    if (!$defaultLoc) {
                        throw new \RuntimeException("Project {$projectId} tidak memiliki lokasi default.");
                    }
                    $row['location_id'] = $defaultLoc->id;
                }
                return $row;
            });

            $payloadUserIds = $payload->pluck('user_id')->unique()->values();

            // === (1) HAPUS yang tidak ada di payload (sinkronisasi) ===
            UserProjectAbsen::where('project_id', $projectId)
                ->whereNotIn('user_id', $payloadUserIds)
                ->delete(); // soft delete karena model pakai SoftDeletes

            // === (2) UPSERT / UPDATE yang ada di payload ===
            $affectedIds = [];
            foreach ($payload as $row) {
                $absen = UserProjectAbsen::updateOrCreate(
                    ['project_id' => $projectId, 'user_id' => $row['user_id']],
                    ['location_id' => $row['location_id']]
                );
                $affectedIds[] = $absen->id;
            }

            DB::commit();

            // Ambil ulang SEMUA data untuk project ini setelah sinkronisasi
            $results = UserProjectAbsen::with([
                'user.role', 'user.divisi', 'project', 'location',
            ])->where('project_id', $projectId)->get();

            return response()->json([
                'status'      => MessageDakama::SUCCESS ?? 'success',
                'status_code' => 200,
                'message'     => "Bulk sync/update berhasil untuk project {$projectId}.",
                'data'        => $results->map(function ($item) {
                    return [
                        'id'      => $item->id,
                        'user'    => [
                            'id'    => data_get($item, 'user.id'),
                            'name'  => data_get($item, 'user.name'),
                            'role'  => [
                                'id'   => data_get($item, 'user.role_id'),
                                'name' => data_get($item, 'user.role.role_name'),
                            ],
                            'divisi'=> [
                                'id'   => data_get($item, 'user.divisi_id'),
                                'name' => data_get($item, 'user.divisi.name'),
                            ],
                        ],
                        'project' => [
                            'id'   => data_get($item, 'project.id'),
                            'name' => data_get($item, 'project.name'),
                        ],
                        'location'=> $item->location,
                    ];
                }),
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal bulk sync/update: " . $th->getMessage());
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
