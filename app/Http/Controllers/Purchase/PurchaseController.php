<?php

namespace App\Http\Controllers\Purchase;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Role;
use App\Models\Company;
use App\Models\Project;
use App\Models\Document;
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
use Illuminate\Support\Facades\Storage;
use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\ByDoctype;
use App\Facades\Filters\Purchase\ByDuedate;
use App\Facades\Filters\Purchase\ByProject;
use App\Facades\Filters\Purchase\ByUpdated;
use App\Http\Requests\Purchase\AcceptRequest;
use App\Http\Requests\Purchase\CreateRequest;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Http\Requests\Purchase\PaymentRequest;
use App\Http\Resources\Purchase\PurchaseCollection;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with([
            'productCompanies.company',     
        ]);

        // $query = Purchase::query();
        
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

   public function createPurchase(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            /*──────────── 1. Generate doc_no & payload header ────────────*/
            $purchaseMax      = Purchase::where('purchase_category_id', $request->purchase_category_id)->max('doc_no');
            $purchaseCategory = PurchaseCategory::find($request->purchase_category_id);

            $headerData = $request->except(['products', 'attachment_file']);
            $headerData = array_merge($headerData, [
                'doc_no'             => $this->generateDocNo($purchaseMax, $purchaseCategory),
                'doc_type'           => Str::upper($purchaseCategory->name),
                'purchase_status_id' => PurchaseStatus::AWAITING,
                'user_id'            => auth()->id(),
                'sub_total_purchase' => 0,            // placeholder
                // 'project_id'         => $request->purchase_id == Purchase::TYPE_OPERATIONAL
                //                             ? null
                //                             : $request->project_id,
            ]);

            /*──────────── 2. Simpan header ────────────*/
            $purchase = Purchase::create($headerData);

            /*──────────── 3. Parse & simpan detail produk ────────────*/
            $detailRows = is_string($request->products)
                ? json_decode($request->products, true, 512, JSON_THROW_ON_ERROR)
                : $request->products;

            if (!is_array($detailRows) || empty($detailRows)) {
                throw new \Exception('Field products tidak valid atau kosong.');
            }

            $grandTotal = 0;
            foreach ($detailRows as $row) {
                // subtotal_harga_product dihitung otomatis di model event
                $product   = $purchase->productCompanies()->create($row);
                $grandTotal += $product->subtotal_harga_product;
            }

            // update subtotal di header
            $purchase->update(['sub_total_purchase' => $grandTotal]);

            /*──────────── 4. Simpan lampiran (jika ada) ────────────*/
            if ($request->hasFile('attachment_file')) {
                foreach ($request->file('attachment_file') as $idx => $file) {
                    $this->saveDocument($purchase, $file, $idx + 1);
                }
            }

            DB::commit();
            return MessageDakama::success("doc no {$purchase->doc_no} has been created");
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }

    /* helper lain (generateDocNo & saveDocument) biarkan seperti yang sudah ada */


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
        $document = $file->store(Purchase::ATTACHMENT_FILE, 'public');
        return $purchase->documents()->create([
            "doc_no" => $purchase->doc_no,
            "file_name" => $purchase->doc_no . '.' . $iteration,
            "file_path" => $document,
            "type_file"      => \App\Models\Document::BUKTI_PEMBELIAN,
        ]);
    }

    public function destroy($docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            Purchase::whereDocNo($docNo)->delete();

            DB::commit();
            return MessageDakama::success("purchase $docNo has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroyDocument($id)
    {
        DB::beginTransaction();

        $purchase = Document::find($id);
        if (!$purchase) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            Storage::delete($purchase->file_path);
            $purchase->delete();

            DB::commit();
            return MessageDakama::success("document $id delete successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

     public function acceptPurchase(AcceptRequest $request, string $docNo)
    {
        DB::beginTransaction();

        try {
            /* 1. Cari purchase — 404 jika tak ada */
            $purchase = Purchase::whereDocNo($docNo)->firstOrFail();

            /* 2. Tolak jika status sudah Paid / Rejected */
            if (in_array($purchase->purchase_status_id, [PurchaseStatus::PAID, PurchaseStatus::REJECTED], true)) {
                return MessageDakama::warning('Purchase sudah final dan tidak bisa di-accept.');
            }

            /* 3. PPh (opsional) */
            $pphTax = null;
            if ($request->filled('pph_id')) {
                $pphTax = Tax::find($request->pph_id);

                if (!$pphTax || strtolower($pphTax->type) !== Tax::TAX_PPH) {
                    return MessageDakama::warning('Tax yang dipilih bukan tipe PPh.');
                }
            }

            /* 4. Tentukan status berdasarkan due_date */
            $statusId = PurchaseStatus::OPEN;
            if ($purchase->due_date) {
                $today   = Carbon::today();
                $dueDate = Carbon::createFromFormat('Y-m-d', $purchase->due_date);

                $statusId = match (true) {
                    $today->greaterThan($dueDate)           => PurchaseStatus::OVERDUE,
                    $today->equalTo($dueDate)               => PurchaseStatus::DUEDATE,
                    default                                 => PurchaseStatus::OPEN,
                };
            }

            /* 5. Data yang akan di-update */
            $updateData = [
                'purchase_status_id' => $statusId,
                'tab'                => Purchase::TAB_VERIFIED,
            ];

            if ($pphTax) {
                $updateData['pph'] = $pphTax->id;
            }

            /* 6. Simpan header */
            $purchase->update($updateData);

            /* 7. Simpan log */
            $purchase->logs()->create([
                'tab'                => $updateData['tab'],
                'purchase_status_id' => $statusId,
                'name'               => auth()->user()->name,
                'note_reject'        => null,
            ]);

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} telah di-accept.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            DB::rollBack();
            return MessageDakama::notFound('Data not found!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }

    public function requestPurchase($docNo)
    {
        DB::beginTransaction();

        try {
            /* 1. Cari purchase */
            $purchase = Purchase::whereDocNo($docNo)->firstOrFail();

            /* Guard: jika sudah paid atau rejected, tolak */
            if (in_array($purchase->purchase_status_id, [PurchaseStatus::PAID, PurchaseStatus::REJECTED])) {
                return MessageDakama::warning('Purchase sudah final dan tidak bisa diajukan pembayaran.');
            }

            /* 2. Hitung status dari due_date */
            $statusId = PurchaseStatus::OPEN;
            if ($purchase->due_date) {
                $today   = Carbon::today();
                $dueDate = Carbon::createFromFormat('Y-m-d', $purchase->due_date);

                $statusId = match (true) {
                    $today->greaterThan($dueDate)           => PurchaseStatus::OVERDUE,
                    $today->eq($dueDate)                    => PurchaseStatus::DUEDATE,
                    default                                 => PurchaseStatus::OPEN,
                };
            }

            /* 3. Update header */
            $purchase->update([
                'purchase_status_id' => $statusId,
                'tab'                => Purchase::TAB_PAYMENT_REQUEST,
            ]);

            /* 4. Tulis log */
            $purchase->logs()->create([
                'tab'                => Purchase::TAB_PAYMENT_REQUEST,
                'purchase_status_id' => $statusId,
                'name'               => auth()->user()->name,
                'note_reject'        => null,
            ]);

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} moved to Payment Request.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            DB::rollBack();
            return MessageDakama::notFound('Data not found!');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function paymentPurchase(PaymentRequest $request, string $docNo)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::whereDocNo($docNo)->firstOrFail();

            /* 1. Tetapkan status dan tab sebagai PAID */
            $statusId = PurchaseStatus::PAID;
            $tabId    = Purchase::TAB_PAID;

            /* 2. Update purchase */
            $purchase->update([
                'purchase_status_id'            => $statusId,
                'tab'                           => $tabId,
                'tanggal_pembayaran_purchase'   => $request->tanggal_pembayaran_purchase,
            ]);

            /* 3. Buat log status pembayaran */
            $purchase->logs()->create([
                'tab'                => $tabId,
                'purchase_status_id' => $statusId,
                'name'               => auth()->user()->name,
                'note_reject'        => null,
            ]);

            /* 4. Upload file bukti pembayaran (jika ada) */
            if ($request->hasFile('attachment_file')) {
                foreach ($request->file('attachment_file') as $idx => $file) {
                    $this->saveDocumentPembayaran($purchase, $file, $idx + 1);
                }
            }

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} telah dibayar.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            DB::rollBack();
            return MessageDakama::notFound('Data not found!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }

    protected function saveDocumentPembayaran($purchase, $file, $iteration)
    {
        $path = $file->store(Purchase::ATTACHMENT_FILE, 'public');

        return $purchase->documents()->create([
            'doc_no'     => $purchase->doc_no,
            'file_name'  => $purchase->doc_no . '.PAY.' . $iteration,
            'file_path'  => $path,
            'type_file'  => \App\Models\Document::BUKTI_PEMBAYARAN, // 2
        ]);
    }

}
