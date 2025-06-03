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
use App\Models\PurchaseProductCompanies;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\ByDoctype;
use App\Facades\Filters\Purchase\ByDuedate;
use App\Facades\Filters\Purchase\ByProject;
use App\Facades\Filters\Purchase\ByUpdated;
use App\Http\Requests\Purchase\AcceptRequest;
use App\Http\Requests\Purchase\CreateRequest;
use App\Http\Requests\Purchase\UpdateRequest;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Http\Requests\Purchase\PaymentRequest;
use App\Http\Resources\Purchase\PurchaseCollection;

class PurchaseController extends Controller
{
    public function getDataProductPurchase()
    {
        // Ambil semua produk + relasi vendor
        $products = \App\Models\PurchaseProductCompanies::with('company')->get();

        $data = $products->map(function ($prod) {

            /* hitung dasar (harga × stok) */
            $base = $prod->harga * $prod->stok;

            /* konversi rate: "11" → 0.11, null → 0 */
            $rate = $prod->ppn
                ? ((float) $prod->ppn > 1 ? (float) $prod->ppn / 100 : (float) $prod->ppn)
                : 0;

            /* rupiah PPN */
            $ppnAmount = round($base * $rate, 2);

            return [
                'id'                     => $prod->id,
                'vendor'                 => [
                    'id'   => $prod->company_id,
                    'name' => $prod->company?->name,
                ],
                'product_name'           => $prod->product_name,
                'harga'                  => $prod->harga,
                'stok'                   => $prod->stok,
                'subtotal_harga_product' => $prod->subtotal_harga_product,
                'ppn' => [
                    'rate'   => $prod->ppn ? (float) $prod->ppn : 0,
                    'amount' => $ppnAmount,
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }

    


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

    public function countingPurchase(Request $rq)
    {
        /* 1) Query + filter pipeline */
        $query = Purchase::query();

        $purchases = app(\Illuminate\Pipeline\Pipeline::class)
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
            ->thenReturn()
            ->with('taxPph')                       // relasi kini sudah ada
            ->get(['tab', 'sub_total_purchase', 'pph']);

        /* 2) Inisialisasi penghitung */
        $tabs = [
            'submit'          => ['count' => 0, 'total' => 0],
            'verified'        => ['count' => 0, 'total' => 0],
            'payment_request' => ['count' => 0, 'total' => 0],
            'paid'            => ['count' => 0, 'total' => 0],
        ];

        /* 3) Loop & akumulasi */
        foreach ($purchases as $p) {

            // hitung PPh (jika ada tax)
            $pphAmount = 0;
            if ($p->pph && $p->taxPph) {
                $rate     = (float) $p->taxPph->percent;      // contoh 2 → 2%
                $rateDec  = $rate > 1 ? $rate / 100 : $rate;  // 0.02
                $pphAmount = round($p->sub_total_purchase * $rateDec, 2);
            }

            $net = $p->sub_total_purchase - $pphAmount;

           switch ($p->tab) {
                case Purchase::TAB_SUBMIT:          $key = 'submit';          break;
                case Purchase::TAB_VERIFIED:        $key = 'verified';        break;
                case Purchase::TAB_PAYMENT_REQUEST: $key = 'payment_request'; break;
                case Purchase::TAB_PAID:            $key = 'paid';            break;
                default:                            continue 2;    
            }

            $tabs[$key]['count']++;
            $tabs[$key]['total'] += $net;
        }

        return response()->json(['data' => $tabs]);
    }


     public function show(string $docNo)
    {
        // ── Ambil purchase + eager-load relasi yang dipakai di PurchaseCollection ──
        $purchase = Purchase::with([
            'company',         
            'user',            
            'project',        
            'documents',      
            'logs',             
            'productCompanies', 
        ])->find($docNo);       

        // ── Handling 404 ──
        if (!$purchase) {
            return MessageDakama::notFound("Purchase dengan Doc No {$docNo} tidak ditemukan.");
        }

        // ── Bungkus ke dalam collection agar kompatibel dengan PurchaseCollection ──
        return new PurchaseCollection(collect([$purchase]));
    }

    /*  public function createPurchase(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
           
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

          
            $purchase = Purchase::create($headerData);

            
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
    */

    public function createPurchase(CreateRequest $request)
    {
        DB::beginTransaction();
        try {
            /* ── 1. HEADER ───────────────────────────── */
            $purchaseMax      = Purchase::where('purchase_category_id', $request->purchase_category_id)->max('doc_no');
            $purchaseCategory = PurchaseCategory::find($request->purchase_category_id);

            $headerData = $request->except(['products', 'products_id', 'attachment_file']);
            $headerData = array_merge($headerData, [
                'doc_no'             => $this->generateDocNo($purchaseMax, $purchaseCategory),
                'doc_type'           => Str::upper($purchaseCategory->name),
                'purchase_status_id' => PurchaseStatus::AWAITING,
                'user_id'            => auth()->id(),
                'sub_total_purchase' => 0,
            ]);

            $purchase = Purchase::create($headerData);

            /* ── 2. DETAIL PRODUK  ───────────────────── */
            $grandTotal = 0;

            /* (A) Input detail baru via products[0][…] */
            if ($request->filled('products')) {
                $rows = is_string($request->products)
                        ? json_decode($request->products, true, 512, JSON_THROW_ON_ERROR)
                        : $request->products;

                foreach ($rows as $row) {
                    $prod = $purchase->productCompanies()->create($row);
                    $grandTotal += $prod->subtotal_harga_product;
                }
            }

            /* (B) Salin produk lama via products_id[] */
            if ($request->filled('products_id')) {
                $templates = PurchaseProductCompanies::whereIn('id', $request->products_id)->get();

                foreach ($templates as $tpl) {
                    $new        = $tpl->replicate(['id', 'doc_no']);
                    $new->doc_no = $purchase->doc_no;
                    $purchase->productCompanies()->save($new);
                    $grandTotal += $new->subtotal_harga_product;
                }
            }

            $purchase->update(['sub_total_purchase' => $grandTotal]);

            /* ── 3. LAMPIRAN ─────────────────────────── */
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

    public function updatePurchase(UpdateRequest $request, string $docNo)
    {
        /* 1. Ambil purchase */
        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageDakama::notFound("Purchase {$docNo} tidak ditemukan.");
        }

        /* 2. Tolak jika sudah Paid */
        if ($purchase->purchase_status_id == PurchaseStatus::PAID) {
            return MessageDakama::warning('Purchase berstatus Paid tidak bisa di-update.');
        }

        DB::beginTransaction();
        try {
            /* 3. HEADER – ambil field kecuali produk & lampiran */
            $headerFields = $request->except(['products', 'attachment_file']);
            if ($headerFields) {
                $purchase->update($headerFields);
            }

            /* 4. PRODUK (jika dikirim) */
            if ($request->has('products')) {
                // hapus detil lama, kemudian masukkan yang baru
                $purchase->productCompanies()->delete();

                $grandTotal = 0;
                foreach ($request->products as $row) {
                    $prod = $purchase->productCompanies()->create($row);
                    $grandTotal += $prod->subtotal_harga_product;
                }
                // perbarui subtotal
                $purchase->update(['sub_total_purchase' => $grandTotal]);
            }

            /* 5. LAMPIRAN (jika dikirim) */
            if ($request->hasFile('attachment_file')) {
                // hapus file bukti pembelian lama
                $old = $purchase->documents()
                                ->where('type_file', \App\Models\Document::BUKTI_PEMBELIAN)
                                ->get();
                foreach ($old as $doc) {
                    Storage::disk('public')->delete($doc->file_path);
                    $doc->delete();
                }
                // simpan file baru
                foreach ($request->file('attachment_file') as $i => $file) {
                    $this->saveDocument($purchase, $file, $i + 1);
                }
            }

            /* 6. SESUAIKAN STATUS WAKTU (hanya bila tab Verified / Payment-Request) */
            if (in_array($purchase->tab,
                [Purchase::TAB_VERIFIED, Purchase::TAB_PAYMENT_REQUEST], true)
            ) {
                $today   = Carbon::today();
                $dueDate = $purchase->due_date
                            ? Carbon::createFromFormat('Y-m-d', $purchase->due_date)
                            : null;

                $status = PurchaseStatus::OPEN;
                if ($dueDate) {
                    if     ($today->gt($dueDate)) $status = PurchaseStatus::OVERDUE;
                    elseif ($today->eq($dueDate)) $status = PurchaseStatus::DUEDATE;
                }

                // update hanya jika berubah
                if ($status !== $purchase->purchase_status_id) {
                    $purchase->update(['purchase_status_id' => $status]);
                }
            }

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} berhasil diperbarui.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
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

    public function undoPurchase(string $docNo)
    {
        /* ── 1. Ambil data ── */
        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageDakama::notFound("Purchase {$docNo} tidak ditemukan.");
        }

        if ($purchase->tab == Purchase::TAB_SUBMIT) {
            return MessageDakama::warning('Tidak bisa undo karena masih di tab Submit.');
        }

        /* ── 2. Tentukan tab & status baru ── */
        $newTab    = $purchase->tab - 1;
        $newStatus = PurchaseStatus::AWAITING;               // default untuk tab Submit

        if (in_array($newTab, [Purchase::TAB_VERIFIED, Purchase::TAB_PAYMENT_REQUEST], true)) {
            $today   = Carbon::today();
            $dueDate = $purchase->due_date
                        ? Carbon::createFromFormat('Y-m-d', $purchase->due_date)
                        : null;

            $newStatus = PurchaseStatus::OPEN;
            if ($dueDate) {
                if     ($today->gt($dueDate)) $newStatus = PurchaseStatus::OVERDUE;
                elseif ($today->eq($dueDate)) $newStatus = PurchaseStatus::DUEDATE;
            }
        }

        /* ── 3. Simpan perubahan ── */
        DB::beginTransaction();
        try {
            /* ── 3a. Jika undo dari Paid → hapus dokumen pembayaran & tanggal ── */
            if ($purchase->tab == Purchase::TAB_PAID) {
                $payDocs = $purchase->documents()
                                    ->where('type_file', \App\Models\Document::BUKTI_PEMBAYARAN)
                                    ->get();

                foreach ($payDocs as $doc) {
                    Storage::disk('public')->delete($doc->file_path);
                    $doc->delete();
                }

                $purchase->tanggal_pembayaran_purchase = null;
            }

            /* ── 3b. Update header (reset PPH hanya jika balik ke Submit) ── */
            $updateData = [
                'tab'                => $newTab,
                'purchase_status_id' => $newStatus,
            ];

            if ($newTab == Purchase::TAB_SUBMIT) {
                $updateData['pph'] = 0;                      // reset hanya untuk Submit
            }

            $purchase->update($updateData);

            /* ── 3c. Log baru ── */
            $purchase->logs()->create([
                'tab'                => $newTab,
                'purchase_status_id' => $newStatus,
                'name'               => auth()->user()->name,
                'note_reject'        => null,
            ]);

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} berhasil di-undo.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }


    public function activatePurchase(UpdateRequest $request, string $docNo)
    {
        /* ── 1. Ambil purchase ── */
        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageDakama::notFound("Purchase {$docNo} tidak ditemukan.");
        }

        /* ── 2. Hanya boleh jika masih REJECTED ── */
        if ($purchase->purchase_status_id !== PurchaseStatus::REJECTED) {
            return MessageDakama::warning('Hanya purchase berstatus Rejected yang bisa di-activate.');
        }

        DB::beginTransaction();
        try {
            /* ────────────────────────────────────────────────────────────────
            * 3. HEADER  – ambil field request kecuali products & attachment
            * ──────────────────────────────────────────────────────────────── */
            $headerData = $request->except(['products', 'attachment_file']);
            $headerData = array_merge($headerData, [
                'purchase_status_id' => PurchaseStatus::AWAITING,
                'tab'                => Purchase::TAB_SUBMIT,
                'reject_note'        => null,                  // hapus catatan reject
            ]);

            $purchase->update($headerData);

            /* ────────────────────────────────────────────────────────────────
            * 4. DETAIL PRODUK  (jika dikirim)
            *    – hapus produk lama lalu insert ulang
            * ──────────────────────────────────────────────────────────────── */
            if ($request->has('products')) {

                // a) hapus detail lama
                $purchase->productCompanies()->delete();

                // b) simpan detail baru
                $detailRows = $request->products;
                $grandTotal = 0;

                foreach ($detailRows as $row) {
                    $product    = $purchase->productCompanies()->create($row);
                    $grandTotal += $product->subtotal_harga_product;
                }

                // c) update subtotal di header
                $purchase->update(['sub_total_purchase' => $grandTotal]);
            }

            /* ────────────────────────────────────────────────────────────────
            * 5. LAMPIRAN (jika dikirim) – hapus bukti pembelian lama, ganti baru
            * ──────────────────────────────────────────────────────────────── */
            if ($request->hasFile('attachment_file')) {

                // a) hapus file fisik & record lama (tipe bukti pembelian = 1)
                $oldDocs = $purchase->documents()
                                    ->where('type_file', \App\Models\Document::BUKTI_PEMBELIAN)
                                    ->get();

                foreach ($oldDocs as $doc) {
                    Storage::disk('public')->delete($doc->file_path);
                    $doc->delete();
                }

                // b) simpan file baru
                foreach ($request->file('attachment_file') as $idx => $file) {
                    $this->saveDocument($purchase, $file, $idx + 1); // helper yg sama dgn createPurchase
                }
            }

            /* ────────────────────────────────────────────────────────────────
            * 6. LOG  – tandai diaktifkan kembali
            * ──────────────────────────────────────────────────────────────── */
            $purchase->logs()->create([
                'tab'                => Purchase::TAB_SUBMIT,
                'purchase_status_id' => PurchaseStatus::AWAITING,
                'name'               => auth()->user()->name,
                'note_reject'        => null,
            ]);

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} berhasil di-activate (status Awaiting).");
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }

    public function rejectPurchase(string $docNo, Request $request)
    {
        /* ── 0. Validasi sederhana (tanpa FormRequest) ── */
        $request->validate([
            'note' => 'required|string|max:500',
        ]);

        /* ── 1. Ambil purchase ── */
        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageDakama::notFound("Purchase {$docNo} tidak ditemukan.");
        }

        /* ── 2. Cegah jika sudah final ── */
        if (in_array($purchase->purchase_status_id, [PurchaseStatus::PAID, PurchaseStatus::REJECTED], true)) {
            return MessageDakama::warning('Purchase sudah final dan tidak bisa direject.');
        }

        DB::beginTransaction();
        try {
            /* ── 3. Log reject ── */
            $purchase->logs()->create([
                'tab'                => Purchase::TAB_SUBMIT,
                'purchase_status_id' => PurchaseStatus::REJECTED,
                'name'               => auth()->user()->name,
                'note_reject'        => $request->note,
            ]);

            /* ── 4. Update header ── */
            $purchase->update([
                'purchase_status_id' => PurchaseStatus::REJECTED,
                'reject_note'        => $request->note,
                'tab'                => Purchase::TAB_SUBMIT,
            ]);

            DB::commit();
            return MessageDakama::success("Purchase {$docNo} berhasil direject.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
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
            /* ── 1. Ambil purchase ── */
            $purchase = Purchase::whereDocNo($docNo)->firstOrFail();

            /* ── 2. Tetapkan status & tab PAID ── */
            $statusId = PurchaseStatus::PAID;    // id = 6
            $tabId    = Purchase::TAB_PAID;      // id = 4

            /* ── 3. Update header ── */
            $purchase->update([
                'purchase_status_id'          => $statusId,
                'tab'                         => $tabId,
                'tanggal_pembayaran_purchase' => $request->tanggal_pembayaran_purchase,
            ]);

            /* ── 4. Catat log ── */
            $purchase->logs()->create([
                'tab'                => $tabId,
                'purchase_status_id' => $statusId,
                'name'               => auth()->user()->name,
                'note_reject'        => null,
            ]);

            /* ── 5. Simpan file bukti pembayaran ── */
            if ($request->hasFile('file_pembayaran')) {
                // bisa single atau array
                $files = $request->file('file_pembayaran');
                $files = is_array($files) ? $files : [$files];

                foreach ($files as $idx => $file) {
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

    public function updatePaymentPurchase(PaymentRequest $request, string $docNo)
    {
        DB::beginTransaction();

        try {
            /* ───────── 1. Ambil purchase ───────── */
            $purchase = Purchase::whereDocNo($docNo)->firstOrFail();

            /* ───────── 2. Update tanggal kalau dikirim ───────── */
            if ($request->filled('tanggal_pembayaran_purchase')) {
                $purchase->update([
                    'tanggal_pembayaran_purchase' => $request->tanggal_pembayaran_purchase,
                ]);
            }

            /* ───────── 3. Overwrite file pembayaran (jika ada upload baru) ───────── */
            if ($request->hasFile('file_pembayaran')) {
                // a) hapus file & record lama
                $oldDocs = $purchase->documents()
                                    ->where('type_file', \App\Models\Document::BUKTI_PEMBAYARAN)
                                    ->get();

                foreach ($oldDocs as $doc) {
                    // hapus fisik file
                    Storage::disk('public')->delete($doc->file_path);
                    // hapus record DB
                    $doc->delete();
                }

                // b) simpan file baru
                $files = $request->file('file_pembayaran');
                $files = is_array($files) ? $files : [$files];

                foreach ($files as $idx => $file) {
                    $this->saveDocumentPembayaran($purchase, $file, $idx + 1);
                }
            }

            DB::commit();
            return MessageDakama::success("Pembayaran untuk {$docNo} berhasil diperbarui.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            DB::rollBack();
            return MessageDakama::notFound("Purchase {$docNo} tidak ditemukan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return MessageDakama::error($e->getMessage());
        }
    }

    /** Helper simpan dokumen bukti pembayaran */
    protected function saveDocumentPembayaran(Purchase $purchase, \Illuminate\Http\UploadedFile $file, int $iteration)
    {
        $path = $file->store(Purchase::ATTACHMENT_FILE, 'public');

        return $purchase->documents()->create([
            'doc_no'    => $purchase->doc_no,
            'file_name' => $purchase->doc_no . '.PAY.' . $iteration,
            'file_path' => $path,
            'type_file' => \App\Models\Document::BUKTI_PEMBAYARAN, // 2
        ]);
    }


}
