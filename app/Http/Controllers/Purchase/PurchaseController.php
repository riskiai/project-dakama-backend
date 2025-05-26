<?php

namespace App\Http\Controllers\Purchase;

use App\Models\Role;
use App\Models\Company;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\ContactType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use App\Models\PurchaseStatus;
use App\Models\PurchaseCategory;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Facades\Filters\Purchase\ByTab;
use App\Facades\Filters\Purchase\ByTax;
use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\ByDoctype;
use App\Facades\Filters\Purchase\ByDuedate;
use App\Facades\Filters\Purchase\ByProject;
use App\Facades\Filters\Purchase\ByUpdated;
use App\Http\Requests\Purchase\CreateRequest;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Http\Resources\Purchase\PurchaseCollection;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::query();
        
        // Tambahkan filter berdasarkan tanggal terkini (komentar saja)
        // $query->whereDate('date', Carbon::today());

        // Terapkan filter berdasarkan peran pengguna
        // if (auth()->user()->role_id == Role::USER) {
        //     $query->where('user_id', auth()->user()->id);
        // }

        $purchases = app(Pipeline::class)
            ->send($query)
            ->through([
                ByDate::class,
                ByUpdated::class,
                ByPurchaseID::class,
                ByTab::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class,
                ByDocType::class, 
                ByDueDate::class,
            ])
            ->thenReturn();

        // Kondisi untuk pengurutan berdasarkan tab
        if ($request->has('tab')) {
            switch ($request->get('tab')) {
                case Purchase::TAB_SUBMIT:
                    $purchases->orderBy('date', 'desc')->orderBy('doc_no', 'desc');
                    break;
                case Purchase::TAB_VERIFIED:
                case Purchase::TAB_PAYMENT_REQUEST:
                    $purchases->orderBy('due_date', 'asc')->orderBy('doc_no', 'asc');
                    break;
                case Purchase::TAB_PAID:
                    $purchases->orderBy('updated_at', 'desc')->orderBy('doc_no', 'desc');
                    break;
                default:
                    $purchases->orderBy('date', 'desc')->orderBy('doc_no', 'desc');
                    break;
            }
        } else {
            // Jika tidak ada tab yang dipilih, urutkan berdasarkan date secara descending
            $purchases->orderBy('date', 'desc')->orderBy('doc_no', 'desc');
        }

        $purchases = $purchases->paginate($request->per_page);

        return new PurchaseCollection($purchases);
    }

    public function indexAll(Request $request) {
         $query = Purchase::query();

        // Tambahkan filter berdasarkan tanggal terkini
        // $query->whereDate('date', Carbon::today());

        // Terapkan filter berdasarkan peran pengguna
        // if (auth()->user()->role_id == Role::USER) {
        //     $query->where('user_id', auth()->user()->id);
        // }
        
        $purchases = app(Pipeline::class)
            ->send($query)
            ->through([
                ByDate::class,
                ByUpdated::class,
                ByPurchaseID::class,
                ByTab::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class,
                ByDocType::class,
                ByDueDate::class,
            ])
            ->thenReturn();

        // kondisi untuk pengurutan berdasarkan tab
        if ($request->has('tab')) {
            if ($request->tab == Purchase::TAB_SUBMIT) {
                $purchases->orderBy('date', 'desc');
            } elseif (in_array($request->tab, [Purchase::TAB_VERIFIED, Purchase::TAB_PAYMENT_REQUEST])) {
                $purchases->orderBy('due_date', 'asc');
            } elseif ($request->tab == Purchase::TAB_PAID) {
                $purchases->orderBy('updated_at', 'desc');
            }
        } else {
            // Jika tidak ada tab yang dipilih, urutkan berdasarkan date secara descending
            $purchases->orderBy('date', 'desc');
        }

        // Ambil daftar pembelian yang sudah diurutkan
        $purchases = $purchases->get(); 

        return new PurchaseCollection($purchases);
    }

    public function countingPurchase() {

    }

    public function show() {

    }

    public function createPurchase(CreateRequest $request) {
        DB::beginTransaction();

        try {
             // Validasi untuk PPN
            $ppn = $request->tax_ppn;
            if (!preg_match('/^\d+(\.\d+)?%?$/', $ppn)) {
                DB::rollBack();
                return MessageDakama::error("Format PPN tidak valid. Harap masukkan nilai PPN dalam format persen tanpa menggunakan koma.");
            }

            // Mendapatkan proyek yang diinginkan
            $project = null;

            // Jika pembelian adalah operasional, maka tidak perlu mengambil proyek
            if ($request->purchase_id == Purchase::TYPE_OPERATIONAL) {
                $project = null; // Set proyek menjadi null untuk pembelian operasional
            } else {
                // Jika pembelian adalah event, maka cek proyek yang diinginkan
                $project = Project::find($request->project_id);

                // Melakukan pengecekan jika proyek tidak ada atau statusnya tidak aktif
                if (!$project || $project->status != Project::ACTIVE) {
                    DB::rollBack();
                    return MessageDakama::error("Proyek tidak tersedia atau tidak aktif.");
                }
            }

            $purchaseMax = Purchase::where('purchase_category_id', $request->purchase_category_id)->max('doc_no');
            $purchaseCategory = PurchaseCategory::find($request->purchase_category_id);

            // $company = Company::find($request->client_id);
            // if ($company->contact_type_id != ContactType::VENDOR) {
            //     return MessageDakama::warning("this contact is not a vendor type");
            // }

            $request->merge([
                'doc_no' => $this->generateDocNo($purchaseMax, $purchaseCategory),
                'doc_type' => Str::upper($purchaseCategory->name),
                'purchase_status_id' => PurchaseStatus::AWAITING,
                // 'company_id' => $company->id,
                'ppn' => $request->tax_ppn,
                'user_id' => auth()->user()->id
            ]);

            // Jika pembelian adalah operasional, set project_id menjadi null
            if ($request->purchase_id == Purchase::TYPE_OPERATIONAL) {
                $request->merge([
                    'project_id' => null,
                ]);
            }

            $purchase = Purchase::create($request->all());

            // Periksa apakah ada file yang dilampirkan sebelum melakukan iterasi foreach
            if ($request->hasFile('attachment_file')) {
                foreach ($request->file('attachment_file') as $key => $file) {
                    $this->saveDocument($purchase, $file, $key + 1);
                }
            }

            DB::commit();
            return MessageDakama::success("doc no $purchase->doc_no has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    protected function generateDocNo($maxPurchase, $purchaseCategory)
    {
        // Jika $purchaseCategory adalah ID atau string, cari objeknya di database
        if (is_numeric($purchaseCategory)) {
            $purchaseCategory = PurchaseCategory::find($purchaseCategory);
        }

        // Pastikan $purchaseCategory adalah objek dan memiliki properti 'short'
        if (!$purchaseCategory || !isset($purchaseCategory->short)) {
            throw new \Exception("Kategori pembelian tidak valid atau tidak ditemukan.");
        }

        // Ambil bagian numerik terakhir dari doc_no
        $numericPart = (int) substr($maxPurchase, strpos($maxPurchase, '-') + 1);

        do {
            // Tambahkan 1 pada bagian numerik dan format menjadi 4 digit
            $nextNumber = sprintf('%04d', $numericPart + 1);
            $docNo = "{$purchaseCategory->short}-$nextNumber";

            // Periksa apakah doc_no ini sudah ada di database
            $exists = Purchase::where('doc_no', $docNo)->exists();

            $numericPart++;
        } while ($exists); // Ulangi hingga menemukan doc_no yang belum ada

        return $docNo;
    }

    protected function saveDocument($purchase, $file, $iteration)
    {
        $document = $file->store(Purchase::ATTACHMENT_FILE);
        return $purchase->documents()->create([
            "doc_no" => $purchase->doc_no,
            "file_name" => $purchase->doc_no . '.' . $iteration,
            "file_path" => $document
        ]);
    }
}
