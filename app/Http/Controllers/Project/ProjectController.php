<?php

namespace App\Http\Controllers\Project;

use App\Models\Role;
use App\Models\Company;
use App\Models\Project;
use App\Models\ContactType;
use Illuminate\Http\Request;
use App\Models\ProjectTermin;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Project\CreateRequest;
use App\Http\Requests\Project\UpdateRequest;
use App\Http\Resources\Project\ProjectCollection;
use App\Http\Requests\Project\PaymentTerminRequest;
use App\Http\Resources\Project\ProjectNameCollection;
use App\Http\Requests\Project\UpdatePaymentTerminRequest;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        // Eager load untuk mengurangi query N+1
        $query->with(['company', 'user', 'tasks', 'tenagaKerja']);

        // Filter berdasarkan status_bonus_project
        if ($request->has('status_bonus_project')) {
            $statusBonus = $request->status_bonus_project;
            $query->where('status_bonus_project', $statusBonus);
        }

        // Filter pencarian
        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('no_dokumen_project', 'like', '%' . $request->search . '%') // Tambahkan ini
                    ->orWhereHas('company', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter berdasarkan status request_status_owner
        if ($request->has('request_status_owner')) {
            $query->where('request_status_owner', $request->request_status_owner);
        }

        // Filter berdasarkan status cost progress
        if ($request->has('status_cost_progres')) {
            $statusCostProgress = $request->status_cost_progres;
            $query->where('status_cost_progres', $statusCostProgress);
        }

        if ($request->has('no_dokumen_project')) {
            $query->where('no_dokumen_project', 'like', '%' . $request->no_dokumen_project . '%');
        }

        if ($request->has('type_projects')) {
            $typeProjects = is_array($request->type_projects)
                ? $request->type_projects
                : explode(',', $request->type_projects);

            $query->whereIn('type_projects', $typeProjects);
        }

        // Filter berdasarkan ID proyek
        if ($request->has('project')) {
            $query->where('id', $request->project);
        }

        // Filter berdasarkan vendor
        if ($request->has('contact')) {
            $query->where('company_id', $request->contact);
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereRaw('STR_TO_DATE(date, "%Y-%m-%d") BETWEEN ? AND ?', [$date[0], $date[1]]);
        }

        if ($request->has('year')) {
            $year = $request->year;
            $query->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [$year]);
        }

        // Terapkan filter berdasarkan peran pengguna
        /*  if ($request->has('role_id')) {
                $roleIds = is_array($request->role_id) ? $request->role_id : explode(',', $request->role_id);
                $query->whereHas('tenagaKerja', function ($q) use ($roleIds) {
                    $q->whereIn('role_id', $roleIds);
                });
            }
        */

        // Filter untuk Supervisor
        /*  if (auth()->user()->role_id == Role::SUPERVISOR) {
            $query->whereHas('tenagaKerja', function ($q) {
                $q->where('user_id', auth()->user()->id);
            });
             }
        */

        // Terapkan filter berdasarkan peran pengguna
        /*  if (auth()->user()->role_id == Role::MARKETING) {
            $query->where(function ($q) {
                $q->where('user_id', auth()->user()->id)
                  ->orWhereHas('tenagaKerja', function ($q) {
                      $q->where('user_id', auth()->user()->id);
                  });
            });
        } */

        // Filter berdasarkan tenaga kerja (tukang)
        /* if ($request->has('tukang')) {
            $tukangIds = explode(',', $request->tukang);
            $query->whereHas('tenagaKerja', function ($query) use ($tukangIds) {
                $query->whereIn('users.id', $tukangIds);
            });
        } */

        // Filter berdasarkan work_type jika ada parameter di request
        /* if ($request->has('work_type')) {
            $workType = $request->work_type;
            if ($workType == 1) {
                $query->whereHas('manPowers', function ($q) {
                    $q->where('work_type', 1);
                });

            } elseif ($workType == 0) {
                $query->whereHas('manPowers', function ($q) {
                    $q->where('work_type', 0);
                });
            }
        } */

        /*  if ($request->has('marketing_id')) {
                $query->whereHas('tenagaKerja', function ($q) use ($request) {
                    $q->where('users.id', $request->marketing_id)
                    ->whereHas('role', function ($roleQuery) {
                        $roleQuery->where('role_id', Role::KARYAWAN);
                    });
                });
            }
        */

        /* if ($request->has('supervisor_id')) {
            $query->whereHas('tenagaKerja', function ($q) use ($request) {
                $q->where('users.id', $request->supervisor_id)
                  ->whereHas('role', function ($roleQuery) {
                      $roleQuery->where('role_id', Role::SUPERVISOR);
                  });
            });
        }
        */

        // Filter berdasarkan divisi (name)
        /*   if ($request->has('divisi_name')) {
            $divisiNames = is_array($request->divisi_name) ? $request->divisi_name : explode(',', $request->divisi_name);

            $query->whereHas('tenagaKerja.divisi', function ($q) use ($divisiNames) {
                $q->whereIn('name', $divisiNames);
            });
        } */

        // Urutkan berdasarkan tahun dan increment ID proyek
        $projects = $query->selectRaw('*, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(id, "-", -2), "-", 1) AS UNSIGNED) as year')
            ->selectRaw('CAST(SUBSTRING_INDEX(id, "-", -1) AS UNSIGNED) as increment')
            ->orderBy('year', 'desc')  // Urutkan berdasarkan tahun (PRO-25 vs PRO-24)
            ->orderBy('increment', 'desc')  // Urutkan berdasarkan increment (001, 002, ...)
            ->orderBy('updated_at', 'desc') // Jika tahun dan increment sama, urutkan berdasarkan updated_at
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page);

        return new ProjectCollection($projects);
    }

    public function projectAll()
    {
        $query = Project::query();

        // Eager load untuk mengurangi query N+1
        $query->with(['company', 'user', 'tenagaKerja']);

        // Tambahkan kondisi untuk menyortir data berdasarkan nama proyek
        $query->orderBy('name', 'asc');

        // Ambil daftar proyek yang sudah diurutkan
        $projects = $query->get();

        return new ProjectCollection($projects);
    }

    public function indexAll()
    {
        $projects = Project::select('id', 'name')->get();
        return new ProjectNameCollection($projects);
    }

    public function nameAll(Request $request)
    {
        // 1.  Base query
        $query = Project::query();

        /* ───────── Global search ───────── */
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('no_dokumen_project', 'like', "%{$search}%")
                    ->orWhereHas('company', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }


        if ($request->filled('project')) {
            $query->where('id', $request->project);
        }

        $projects = $query->select('id', 'name')
            ->orderBy('name')
            ->when(!$request->filled('search'), fn($q) => $q->limit(5))
            ->get();

        return new ProjectNameCollection($projects);
    }

    public function show($id)
    {
        $project = Project::with(['company', 'user', 'tenagaKerja', 'tasks'])
            ->find($id);

        if (!$project) {
            return MessageDakama::notFound("Project dengan ID $id tidak ditemukan.");
        }

        return new ProjectCollection(collect([$project]));
    }

    /* public function counting(Request $request)
    {
        $query = Project::query();

        if ($request->has('request_status_owner')) {
            $query->where('request_status_owner', $request->request_status_owner);
        }

        if ($request->has('no_dokumen_project')) {
            $query->where('no_dokumen_project', 'like', '%' . $request->no_dokumen_project . '%');
        }

        if ($request->has('type_projects')) {
            $typeProjects = is_array($request->type_projects)
                ? $request->type_projects
                : explode(',', $request->type_projects);

            $query->whereIn('type_projects', $typeProjects); // Filter proyek berdasarkan type_projects
        }

        // Lakukan filter berdasarkan project jika ada
        if ($request->has('project')) {
            $query->where('id', $request->project);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor')) {
            $query->where('company_id', $request->vendor);
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereRaw('STR_TO_DATE(date, "%Y-%m-%d") BETWEEN ? AND ?', [$date[0], $date[1]]);
        }

        if ($request->has('year')) {
            $year = $request->year;
            $query->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [$year]);
        }

        // Ambil seluruh data tanpa paginasi
        $collection = $query->get();

        // Menghitung total billing, cost_estimate, dan margin untuk seluruh data
        $totalBilling = $collection->sum('billing');
        $totalCostEstimate = $collection->sum('cost_estimate');
        $totalMargin = $collection->sum('margin');

        // Menghitung persentase margin terhadap billing
        $percent = ($totalBilling > 0) ? ($totalMargin / $totalBilling) * 100 : 0;
        $percent = round($percent, 2) . '%';

        $totalProjects = $collection->count();

        // Response data
        return response()->json([
            "billing" => $totalBilling,
            "cost_estimate" => $totalCostEstimate,
            "margin" => $totalMargin,
            "percent" => $percent,
            "total_projects" => $totalProjects,
            // "harga_type_project_total_borongan" => $totalHargaType,
            // "total_harga_borongan_spb" => $totalHargaBorongan,
        ]);
    } */

    public function counting(Request $request)
    {
        // Mulai query proyek + sum nominal budget (alias: cost_estimate_from_budget)
        $query = Project::query()
            ->withSum('budgetsDirect as cost_estimate_from_budget', 'nominal');

        /* -----------------------------------------------------------------
         |  FILTER SECTION
         |-----------------------------------------------------------------*/
        if ($request->filled('request_status_owner')) {
            $query->where('request_status_owner', $request->request_status_owner);
        }

        if ($request->filled('no_dokumen_project')) {
            $query->where('no_dokumen_project', 'like', "%{$request->no_dokumen_project}%");
        }

        if ($request->filled('type_projects')) {
            $typeProjects = is_array($request->type_projects)
                ? $request->type_projects
                : explode(',', $request->type_projects);

            $query->whereIn('type_projects', $typeProjects);
        }

        if ($request->filled('project')) {
            $query->where('id', $request->project);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor')) {
            $query->where('company_id', $request->vendor);
        }

        if ($request->filled('date')) {
            // format input: "[2025-01-01, 2025-12-31]" atau "2025-01-01, 2025-12-31"
            $range = str_replace(['[', ']'], '', $request->date);
            [$start, $end] = array_map('trim', explode(',', $range));

            $query->whereRaw(
                'STR_TO_DATE(`date`, "%Y-%m-%d") BETWEEN ? AND ?',
                [$start, $end]
            );
        }

        if ($request->filled('year')) {
            $query->whereRaw(
                'YEAR(STR_TO_DATE(`date`, "%Y-%m-%d")) = ?',
                [$request->year]
            );
        }

        /* -----------------------------------------------------------------
         |  EXECUTE & AGGREGATE
         |-----------------------------------------------------------------*/
        $collection = $query->get();

        // Total billing (harga proyek)
        $totalBilling = (float) $collection->sum('billing');

        // Total estimasi biaya (dari budget nominal)
        $totalCostEstimate = (float) $collection->sum('cost_estimate_from_budget');

        // Total margin = billing - cost_estimate
        $totalMargin = $totalBilling - $totalCostEstimate;

        // Profit margin % (dibulatkan 2 desimal)
        $percent = $totalBilling > 0
            ? round(($totalMargin / $totalBilling) * 100, 2)
            : 0.0;

        // Hasil akhir
        return response()->json([
            'billing'        => $totalBilling,
            'cost_estimate'  => $totalCostEstimate,
            'margin'         => $totalMargin,
            'percent'        => $percent . '%',  // tambahkan simbol %
            'total_projects' => $collection->count(),
        ]);
    }


    public function createProject(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            // Temukan perusahaan berdasarkan client_id
            $company = Company::find($request->client_id);
            if (!$company || $company->contact_type_id != ContactType::CLIENT) {
                return MessageDakama::warning("This contact is not a client type");
            }

            // Persiapkan data yang akan disimpan
            $project = new Project();

            // Ambil tahun dari tanggal input
            $year = date('y', strtotime($request->date)); // Ambil tahun dua digit dari input tanggal

            // Generate ID dengan mengirimkan tahun ke function generateSequenceNumber
            $sequenceNumber = Project::generateSequenceNumber($year);
            $project->id = 'PRO-' . $year . '-' . $sequenceNumber; // Generate ID

            // Isi field lainnya
            $project->name = $request->name;
            $project->billing = $request->billing;
            $project->cost_estimate = $request->cost_estimate;
            $project->margin = $request->margin;
            $project->percent = $request->percent;
            $project->date = $request->date;
            $project->company_id = $company->id;
            $project->user_id = auth()->user()->id;
            $project->request_status_owner = Project::DEFAULT_STATUS;
            $project->status_bonus_project = Project::DEFAULT_STATUS_NO_BONUS;
            $project->type_projects = $request->type_projects;
            $project->no_dokumen_project = $request->no_dokumen_project;


            // Simpan file ke disk public
            $project->file = $request->hasFile('attachment_file')
                ? $request->file('attachment_file')->store(Project::ATTACHMENT_FILE, 'public')
                : null;

            // Simpan proyek ke database
            $project->save();

            // Ambil produk_id dan user_id dari request
            $userIds = array_filter($request->input('user_id', []));
            $tasksIds = array_filter($request->input('tasks_id', []));

            /*
                if (auth()->user()->role_id == Role::MARKETING) {
                    $userIds[] = auth()->user()->id;
                }
            */

            // Sinkronisasi user_id di pivot table hanya jika ada user_id yang valid
            if (!empty($userIds)) {
                $project->tenagaKerja()->syncWithoutDetaching($userIds);
            }

            if (!empty($tasksIds)) {
                $project->tasks()->syncWithoutDetaching($tasksIds); // Sinkronkan produk ke pivot table
            }

            if ($request->has('locations')) {
                $project->locations()->create([
                    ...$request->locations[0],
                    "is_default" => true
                ]);
            }

            // Commit transaksi
            DB::commit(); // Commit transaksi
            return MessageDakama::success("Project created successfully. $project->id", $project);
        } catch (\Exception $e) {
            // Rollback jika terjadi error
            DB::rollBack();
            Log::error("Error creating project: " . $e->getMessage());
            return MessageDakama::error("Error creating project.");
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction(); // Mulai transaksi manual

        try {
            // Temukan proyek berdasarkan ID yang diberikan
            $project = Project::find($id);

            if (!$project) {
                return MessageDakama::notFound('Project data not found!');
            }

            // Temukan perusahaan berdasarkan client_id yang dikirimkan di request
            $company = Company::find($request->client_id);
            if (!$company) {
                throw new \Exception("Client data not found!");
            }

            // Gabungkan data client_id ke dalam request
            $request->merge([
                'company_id' => $company->id,
            ]);

            $currentStatus = $project->request_status_owner;

            // Ambil tahun dari tanggal baru dan tanggal lama
            $newYear = date('y', strtotime($request->date));  // Tahun dari tanggal baru
            $currentYear = date('y', strtotime($project->date)); // Tahun dari tanggal lama

            if ($newYear != $currentYear) {
                // Generate ID baru berdasarkan tahun baru
                $newId = 'PRO-' . $newYear . '-' . Project::generateSequenceNumber($newYear);

                // Tambahkan ID baru ke tabel projects (simpan sementara)
                Project::create([
                    'id' => $newId,
                    'name' => $project->name,
                    'billing' => $project->billing,
                    'cost_estimate' => $project->cost_estimate,
                    'margin' => $project->margin,
                    'percent' => $project->percent,
                    'date' => $request->date,
                    'company_id' => $project->company_id,
                    'user_id' => $project->user_id,
                    'request_status_owner' => $project->request_status_owner,
                    'type_projects' => $project->type_projects,
                    'no_dokumen_project' => $project->no_dokumen_project,
                    'file'                 => $project->file, 
                ]);

                // Update foreign key di tabel project_user_produk
                DB::table('projects_user_tasks')
                    ->where('project_id', $id)
                    ->update(['project_id' => $newId]);

                // Update foreign key di tabel spb_projects
                // DB::table('spb_projects')
                //     ->where('project_id', $id)
                //     ->update(['project_id' => $newId]);

                // Update foreign key di tabel man_powers
                // DB::table('man_powers')
                //     ->where('project_id', $id)
                //     ->update(['project_id' => $newId]);

                // Hapus ID lama dari tabel projects
                $project->delete();

                // Update variabel proyek ke ID baru
                $project = Project::find($newId);
            }

            if ($currentStatus == Project::REJECTED) {
                $project->request_status_owner = Project::PENDING;
            }

            // Logika perubahan status otomatis
            if ($project->request_status_owner == Project::ACTIVE) {
                $project->request_status_owner = Project::PENDING; // Set status ke pending
            }

            // Simpan perubahan status
            $project->save();

            // Jika ada file baru (attachment_file), hapus file lama dan simpan yang baru
            if ($request->hasFile('attachment_file')) {
                if ($project->file) {
                    Storage::delete($project->file);
                }
                $project->file = $request->file('attachment_file')->store(Project::ATTACHMENT_FILE, 'public');
            }

            // Update proyek dengan data baru
            $project->update($request->except(['tasks_id', 'user_id']));

            // Ambil data produk_id dan user_id dari request
            $tasksIds = $request->input('tasks_id', []);
            $userIds = $request->input('user_id', []);

            // Sinkronkan data produk dan user pada tabel pivot
            $project->tasks()->sync(array_unique($tasksIds));
            $project->tenagaKerja()->sync(array_unique($userIds));

            // Commit transaksi
            DB::commit();

            return MessageDakama::success("Project {$project->name} has been updated successfully.");
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function accept($id)
    {
        DB::beginTransaction();

        // Pastikan user yang login memiliki role OWNER
        if (!auth()->user()->hasRole(Role::OWNER)) {
            return response()->json([
                'message' => 'Access denied! Only owners can accept projects.'
            ], 403);
        }

        $project = Project::find($id);
        if (!$project) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $project->update([
                "request_status_owner" => Project::ACTIVE
            ]);

            DB::commit();
            return MessageDakama::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function reject($id)
    {
        DB::beginTransaction();

        $project = Project::find($id);
        if (!$project) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $project->update([
                "request_status_owner" => Project::REJECTED
            ]);

            DB::commit();
            return MessageDakama::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function closed($id)
    {
        DB::beginTransaction();

        // Pastikan user yang login memiliki role OWNER
        if (!auth()->user()->hasRole(Role::OWNER)) {
            return response()->json([
                'message' => 'Access denied! Only owners can closed projects.'
            ], 403);
        }

        $project = Project::find($id);
        if (!$project) {
            return MessageDakama::notFound('data not found!');
        }

        // Validasi bahwa status owner adalah ACTIVE
        if ($project->request_status_owner != Project::ACTIVE) {
            return response()->json([
                'message' => 'Project cannot be closed because the owner status is not ACTIVE.'
            ], 400);
        }

        try {
            $project->update([
                "request_status_owner" => Project::CLOSED
            ]);

            DB::commit();
            return MessageDakama::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function cancel($id)
    {
        DB::beginTransaction();

        if (!auth()->user()->hasRole(Role::OWNER)) {
            return response()->json([
                'message' => 'Access denied! Only owners can cancel projects.'
            ], 403);
        }

        $project = Project::find($id);
        if (!$project) {
            return MessageDakama::notFound('data not found');
        }

        try {
            $project->update([
                "request_status_owner" => Project::CANCEL
            ]);

            DB::commit();
            return MessageDakama::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    /* Bonus Project */
    public function bonus($id)
    {
        DB::beginTransaction();

        // Pastikan user yang login memiliki role OWNER
        if (!auth()->user()->hasRole(Role::OWNER)) {
            return response()->json([
                'message' => 'Access denied! Only owners can add bonus projects.'
            ], 403);
        }

        $project = Project::find($id);
        if (!$project) {
            return MessageDakama::notFound('data not found!');
        }

        // Validasi bahwa status owner adalah ACTIVE
        if ($project->request_status_owner != Project::CLOSED) {
            return response()->json([
                'message' => 'Project cannot be closed because the owner status is Closed.'
            ], 400);
        }

        try {
            $project->update([
                "status_bonus_project" => Project::SUDAH_DIKASIH_BONUS
            ]);

            DB::commit();
            return MessageDakama::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();

        $project = Project::find($id);
        if (!$project) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            // Temukan proyek berdasarkan ID, atau akan gagal jika tidak ditemukan

            // SpbProject::where('project_id', $id)->update(['project_id' => null]);

            \App\Models\Purchase::where('project_id', $project->id)
                ->update(['project_id' => null]);

            // Hapus hubungan many-to-many terlebih dahulu jika ada
            $project->tasks()->detach();
            $project->tenagaKerja()->detach();

            // Hapus file terkait proyek (jika ada)
            if ($project->file) {
                Storage::delete($project->file);
            }

            // Hapus proyek dari database
            $project->delete();

            // Commit transaksi
            DB::commit();

            return MessageDakama::success("Project $project->name has been deleted successfully.");
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    /* Project Termin */
    public function paymentTermin(PaymentTerminRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            /*--------------------------------------------------
            | 1.  Lock proyek agar tidak ada race condition
            --------------------------------------------------*/
            $project = Project::where('id', $id)->lockForUpdate()->firstOrFail();

            /*--------------------------------------------------
            | 2.  Handle file upload (jika ada)
            --------------------------------------------------*/
            $fileAttachment = null;
            if ($request->hasFile('attachment_file_termin_proyek')) {
                $file = $request->file('attachment_file_termin_proyek');

                if (is_array($file)) {
                    throw new \Exception(
                        'Hanya satu file yang diperbolehkan untuk attachment_file_termin_proyek'
                    );
                }
                $fileAttachment = $file->store(Project::ATTACHMENT_FILE_TERMIN_PROYEK, 'public');
            }

            /*--------------------------------------------------
            | 3.  Pastikan type_termin_proyek valid
            --------------------------------------------------*/
            $typeTerminStr = (string) $request->input('type_termin_proyek');

            /*--------------------------------------------------
            | 4.  Idempotency check (hindari duplikat persis)
            |     Contoh kriteria: tanggal + nominal sama
            --------------------------------------------------*/
            $exists = $project->projectTermins()
                ->where('harga_termin', $request->harga_termin_proyek)
                ->where('tanggal_payment', $request->payment_date_termin_proyek)
                ->exists();

            if ($exists) {
                // Jika sudah pernah tersimpan, jangan insert lagi
                DB::rollBack();
                return response()->json([
                    'status'  => 'IGNORED',
                    'message' => 'Termin pembayaran sudah tercatat.',
                ], 200);
            }

            /*--------------------------------------------------
            | 5.  Simpan termin baru
            --------------------------------------------------*/
            $termin = ProjectTermin::create([
                'project_id'               => $project->id,
                'harga_termin'             => (float) $request->harga_termin_proyek,
                'deskripsi_termin'         => $request->deskripsi_termin_proyek,
                'type_termin'              => $typeTerminStr,
                'tanggal_payment'          => $request->payment_date_termin_proyek,
                'file_attachment_pembayaran' => $fileAttachment,
            ]);

            /*--------------------------------------------------
            | 6.  Re-hitung total termin setelah insert
            --------------------------------------------------*/
            $totalTermin = $project->projectTermins()->sum('harga_termin');
            $isLunas     = $totalTermin >= (float) $project->billing;

            /*--------------------------------------------------
            | 7.  Ambil termin terbaru utk metadata proyek
            --------------------------------------------------*/
            $latestTermin = $project->projectTermins()
                ->orderBy('tanggal_payment', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            /*--------------------------------------------------
            | 8.  Update kolom proyek
            --------------------------------------------------*/
            $project->update([
                'file_pembayaran_termin'     => $latestTermin?->file_attachment_pembayaran,
                'deskripsi_termin_proyek'    => $latestTermin?->deskripsi_termin,
                'payment_date_termin_proyek' => $latestTermin?->tanggal_payment,
                'harga_termin_proyek'        => $totalTermin,
                'sisa_pembayaran_termin'     => max(0, $project->billing - $totalTermin),
                'type_termin_proyek'         => json_encode([
                    'id'   => $isLunas
                        ? Project::TYPE_TERMIN_PROYEK_LUNAS        // 2
                        : Project::TYPE_TERMIN_PROYEK_BELUM_LUNAS, // 1
                    'name' => $isLunas ? 'Lunas' : 'Belum Lunas',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Termin pembayaran berhasil ditambahkan!',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'  => 'ERROR',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function updateTermin(UpdatePaymentTerminRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $project = Project::with(['projectTermins'])->findOrFail($id);

            if (!$project) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Project not found!',
                ], 404);
            }

            // Variabel untuk menyimpan data termin terakhir yang diupdate
            $lastUpdatedTerminData = null;

            // Loop untuk memperbarui setiap termin dalam request
            foreach ($request->riwayat_termin as $terminData) {
                $termin = $project->projectTermins->where('id', $terminData['id'])->first();

                if (!$termin) {
                    return response()->json([
                        'status' => 'ERROR',
                        'message' => "Termin with ID {$terminData['id']} not found!",
                    ], 404);
                }

                // **Cek dan Update File Attachment**
                $fileAttachmentPath = $termin->file_attachment_pembayaran;

                if ($request->hasFile("riwayat_termin.{$terminData['id']}.attachment_file_termin_proyek")) {
                    $file = $request->file("riwayat_termin.{$terminData['id']}.attachment_file_termin_proyek");

                    if ($file->isValid()) {
                        // Hapus file lama jika ada
                        if ($fileAttachmentPath && Storage::disk('public')->exists($fileAttachmentPath)) {
                            Storage::disk('public')->delete($fileAttachmentPath);
                        }

                        // Simpan file baru
                        $fileAttachmentPath = $file->store(Project::ATTACHMENT_FILE_TERMIN_PROYEK, 'public');
                    } else {
                        return response()->json([
                            'status' => 'ERROR',
                            'message' => 'File upload failed',
                        ], 400);
                    }
                }

                // **Update Data Termin**
                $termin->update([
                    'harga_termin' => (float) $terminData['harga_termin_proyek'],
                    'deskripsi_termin' => $terminData['deskripsi_termin_proyek'],
                    'type_termin' => (string) $terminData['type_termin_proyek'],
                    'tanggal_payment' => $terminData['payment_date_termin_proyek'],
                    'file_attachment_pembayaran' => $fileAttachmentPath, // Simpan string path file
                ]);

                // Simpan data termin terakhir yang diupdate
                $lastUpdatedTerminData = $terminData;
            }

            // **Update Deskripsi & Type Termin di Project**
            if ($lastUpdatedTerminData) {
                $project->update([
                    'deskripsi_termin_proyek' => $lastUpdatedTerminData['deskripsi_termin_proyek'],
                    'type_termin_proyek' => json_encode([
                        "id" => (string) $lastUpdatedTerminData['type_termin_proyek'],
                        "name" => $lastUpdatedTerminData['type_termin_proyek'] == Project::TYPE_TERMIN_PROYEK_LUNAS ? "Lunas" : "Belum Lunas",
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }

            // **Hitung ulang total harga_termin**
            $totalHargaTermin = $project->projectTermins()->sum('harga_termin');
            $project->update([
                'harga_termin_proyek' => (float) $totalHargaTermin,
            ]);

            // **Ambil termin terbaru berdasarkan `tanggal_payment` & `created_at`**
            $latestTermin = $project->projectTermins()
                ->orderBy('tanggal_payment', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestTermin) {
                $project->update([
                    'file_pembayaran_termin' => is_string($latestTermin->file_attachment_pembayaran) ? $latestTermin->file_attachment_pembayaran : null,
                    'payment_date_termin_proyek' => $latestTermin->tanggal_payment,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Termin updated successfully!',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'ERROR',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function deleteTermin(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            /* -----------------------------------------------------------
            * 1. Validasi payload
            * ----------------------------------------------------------*/
            $terminIds = $request->input('riwayat_termin');
            if (!is_array($terminIds) || empty($terminIds)) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => '"riwayat_termin" harus berupa array ID termin',
                ], 400);
            }

            /* -----------------------------------------------------------
            * 2. Ambil proyek & termin yang dipilih
            * ----------------------------------------------------------*/
            $project = Project::findOrFail($id);

            $termins = ProjectTermin::where('project_id', $project->id)
                ->whereIn('id', $terminIds)
                ->get();

            if ($termins->isEmpty()) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => 'ID termin tidak ditemukan pada proyek ini',
                ], 404);
            }

            /* -----------------------------------------------------------
            * 3. Hapus termin + file
            * ----------------------------------------------------------*/
            foreach ($termins as $termin) {
                if (
                    $termin->file_attachment_pembayaran &&
                    Storage::disk('public')->exists($termin->file_attachment_pembayaran)
                ) {
                    Storage::disk('public')->delete($termin->file_attachment_pembayaran);
                }
                $termin->delete();
            }

            /* -----------------------------------------------------------
            * 4. Hitung ulang termin yang tersisa
            * ----------------------------------------------------------*/
            $remainingTermins = $project->projectTermins()
                ->orderBy('tanggal_payment', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalTermin = $remainingTermins->sum('harga_termin');
            $isLunas     = $totalTermin >= (float) $project->billing;
            $latest      = $remainingTermins->first();   // null jika semua termin terhapus

            /* -----------------------------------------------------------
            * 4B. Normalisasi status setiap termin
            * ----------------------------------------------------------*/
            if ($isLunas) {
                // Set semua → Belum Lunas terlebih dulu
                ProjectTermin::where('project_id', $project->id)
                    ->update(['type_termin' => Project::TYPE_TERMIN_PROYEK_BELUM_LUNAS]);

                // Mark termin terbaru sebagai Lunas
                if ($latest) {
                    $latest->update([
                        'type_termin' => Project::TYPE_TERMIN_PROYEK_LUNAS,
                    ]);
                }
            } else {
                // Pastikan tidak ada termin berstatus Lunas
                ProjectTermin::where('project_id', $project->id)
                    ->where('type_termin', Project::TYPE_TERMIN_PROYEK_LUNAS)
                    ->update(['type_termin' => Project::TYPE_TERMIN_PROYEK_BELUM_LUNAS]);
            }

            /* -----------------------------------------------------------
            * 5. Update kolom proyek
            * ----------------------------------------------------------*/
            $project->update([
                // a. info termin terakhir (nullable)
                'deskripsi_termin_proyek'   => $latest?->deskripsi_termin,
                'file_pembayaran_termin'    => $latest?->file_attachment_pembayaran,
                'payment_date_termin_proyek' => $latest?->tanggal_payment,

                // b. total & sisa
                'harga_termin_proyek'       => $totalTermin,
                'sisa_pembayaran_termin'    => max(0, $project->billing - $totalTermin),

                // c. status Lunas / Belum Lunas
                'type_termin_proyek'        => json_encode([
                    'id'   => $isLunas
                        ? Project::TYPE_TERMIN_PROYEK_LUNAS
                        : Project::TYPE_TERMIN_PROYEK_BELUM_LUNAS,
                    'name' => $isLunas ? 'Lunas' : 'Belum Lunas',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            DB::commit();

            return response()->json([
                'status'                 => 'SUCCESS',
                'message'                => 'Selected termin(s) deleted successfully!',
                'remaining_total_termin' => $totalTermin,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'  => 'ERROR',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
