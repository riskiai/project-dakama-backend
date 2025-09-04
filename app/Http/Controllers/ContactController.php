<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ContactType;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Contact\CreateRequest;
use App\Http\Requests\Contact\UpdateRequest;
use App\Http\Resources\Contact\ContactCollection;
use App\Http\Resources\Contact\ContactAllCollection;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        // inisiasi company/contact dalam bentuk query, supaya bisa dilakukan untuk filtering
        $query = Company::query();

        if ($request->has('contact_type')) {
            $query->where('contact_type_id', $request->contact_type);
        }

        // pembuatan kondisi ketika params search
        if ($request->has('search')) {
            // maka lakukan query bersarang seperti dibawah ini
            // $query->where(func...{}) => query akan berjalan jika kondisi didalamnya terpenuhi
            $query->where(function ($query) use ($request) {
                // query ini digunakan untuk filtering data
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('pic_name', 'like', "%$request->search%")
                    ->orWhere('phone', 'like', "%$request->search%")
                    ->orWhere('bank_name', 'like', "%$request->search%")
                    ->orWhere('account_name', 'like', "%$request->search%")
                    ->orWhere('account_number', 'like', "%$request->search%")
                    ->orWhereHas('contactType', function ($query) use ($request) { // query ini digunakan jika ada yang mencari ke arah relasinya, artinya sama seperti baris ke 26
                        $query->where('name', 'like', "%$request->search%");
                    });
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        // Filter Berdasarkan Vendor Category
        if ($request->has('vendor_category')) {
            $query->where('vendor_category', 'like', '%' . $request->vendor_category . '%');
        }


        // keluaran dari index ini merupakan paginate
        $contacts = $query->paginate($request->per_page);

        // untuk index pengelolaan datanya terpisah file
        // untuk mempertahankan filtering bawaan paginate laravel
        // pembuatan file bisa menggunakan command `php artisan make:resource NamaFile`
        return new ContactCollection($contacts);
    }

    public function contactall(Request $request)
    {
        // inisiasi company/contact dalam bentuk query, supaya bisa dilakukan untuk filtering
        $query = Company::query();

        if ($request->has('contact_type')) {
            $query->where('contact_type_id', $request->contact_type);
        }

        // pembuatan kondisi ketika params search
        if ($request->has('search')) {
            // maka lakukan query bersarang seperti dibawah ini
            // $query->where(func...{}) => query akan berjalan jika kondisi didalamnya terpenuhi
            $query->where(function ($query) use ($request) {
                // query ini digunakan untuk filtering data
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('pic_name', 'like', "%$request->search%")
                    ->orWhere('phone', 'like', "%$request->search%")
                    ->orWhere('bank_name', 'like', "%$request->search%")
                    ->orWhere('account_name', 'like', "%$request->search%")
                    ->orWhere('account_number', 'like', "%$request->search%")
                    ->orWhereHas('contactType', function ($query) use ($request) { // query ini digunakan jika ada yang mencari ke arah relasinya, artinya sama seperti baris ke 26
                        $query->where('name', 'like', "%$request->search%");
                    });
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        // Filter Berdasarkan Vendor Category
        if ($request->has('vendor_category')) {
            $query->where('vendor_category', 'like', '%' . $request->vendor_category . '%');
        }

        // Tambahkan kondisi untuk menyortir data berdasarkan nama perusahaan jika jenis kontak adalah vendor
        if ($request->has('contact_type') && $request->contact_type == ContactType::VENDOR) {
            $query->orderBy('name', 'asc');
        }


        // keluaran dari index ini merupakan paginate
        $contacts = $query->get();

        // untuk index pengelolaan datanya terpisah file
        // untuk mempertahankan filtering bawaan paginate laravel
        // pembuatan file bisa menggunakan command `php artisan make:resource NamaFile`
        return new ContactCollection($contacts);
    }

    /*public function companyAll(Request $request)
        {
            $query = Company::with('contactType')->select('id', 'contact_type_id', 'name');

            if ($request->has('contact_type')) {
                $query->where('contact_type_id', $request->contact_type);
            }

            $companies = $query->get();

            return new ContactAllCollection($companies);
        } */

    public function companyAll(Request $request)
    {
        $keyword = trim($request->input('search', ''));

        $query = Company::with('contactType')
                        ->select('id', 'contact_type_id', 'name', 'vendor_category');

        if ($request->has('contact_type')) {
            $query->where('contact_type_id', $request->contact_type);
        }

        // Filter Berdasarkan Vendor Category
        if ($request->has('vendor_category')) {
            $query->where('vendor_category', 'like', '%' . $request->vendor_category . '%');
        }

        if ($keyword !== '') {
            // Ada kata kunci → filter nama / ID, tanpa limit
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('id', 'like', "%{$keyword}%");
            });
        } else {
            // Tidak ada kata kunci → cukup 5 entri teratas
            $query->limit(5);
        }

        $companies = $query->orderBy('name')->get();

        return new ContactAllCollection($companies);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        $contactType = ContactType::find($request->contact_type);

        try {
            // Memeriksa apakah file attachment_npwp ada
            $npwpPath = "-";
            if ($request->hasFile('attachment_npwp')) {
                // Simpan file di disk 'public'
                $npwpPath = $request->file('attachment_npwp')->store(Company::ATTACHMENT_NPWP, 'public');
            }

            $email = "-";
            if ($request->has('email')) {
                $email = $request->email ?? "-";
            }

            $filePath = $request->hasFile('attachment_file') ? $request->file('attachment_file')->store(Company::ATTACHMENT_FILE, 'public') : null;

            $requestData = $request->all();
            $requestData['contact_type_id'] = $contactType->id;
            $requestData['npwp'] = $npwpPath;
            $requestData['file'] = $filePath;
            $requestData['email'] = $email;

            $contact = Company::create($requestData);

            DB::commit();
            return MessageDakama::success("contact $contact->name has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $contactType = ContactType::find($request->contact_type);
        $request->merge([
            'contact_type_id' => $contactType->id,
        ]);

        $contact = Company::find($id);
        if (!$contact) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            // Memeriksa apakah file attachment_npwp ada dan menyimpannya
            $npwpPath = $contact->npwp; // Gunakan path lama jika file tidak di-upload
            if ($request->hasFile('attachment_npwp')) {
                // Hapus file lama jika ada
                if ($npwpPath && Storage::disk('public')->exists($npwpPath)) {
                    Storage::disk('public')->delete($npwpPath);
                }
                // Simpan file baru
                $npwpPath = $request->file('attachment_npwp')->store(Company::ATTACHMENT_NPWP, 'public');
            }

            // Memeriksa apakah file attachment_file ada dan menyimpannya
            $filePath = $contact->file; // Gunakan path lama jika file tidak di-upload
            if ($request->hasFile('attachment_file')) {
                // Hapus file lama jika ada
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
                // Simpan file baru
                $filePath = $request->file('attachment_file')->store(Company::ATTACHMENT_FILE, 'public');
            }

            // Update data lainnya
            $requestData = $request->all();
            $requestData['contact_type_id'] = $contactType->id;
            $requestData['npwp'] = $npwpPath;
            $requestData['file'] = $filePath;

            $contact->update($requestData);

            DB::commit();
            return MessageDakama::success("contact $contact->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function show($id)
    {
        $contact = Company::find($id);
        if (!$contact) {
            return MessageDakama::notFound('data not found!');
        }

        // Mengambil URL attachment_npwp dan attachment_file hanya jika file tersebut ada
        $attachment_npwp = $contact->npwp ? asset("storage/$contact->npwp") : null;
        $attachment_file = $contact->file ? asset("storage/$contact->file") : null;

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' =>  [
                "id" => $contact->id,
                "uuid" => $this->generateUuid($contact),
                "contact_type" => [
                    "id" => $contact->contactType->id,
                    "name" => $contact->contactType->name,
                ],
                "name" => $contact->name,
                "vendor_category" => $contact->vendor_category,
                "address" => $contact->address,
                "attachment_npwp" => $attachment_npwp,
                "pic_name" => $contact->pic_name,
                "phone" => $contact->phone,
                "email" => $contact->email,
                "attachment_file" => $attachment_file,
                "bank_name" => $contact->bank_name,
                "branch" => $contact->branch,
                "account_name" => $contact->account_name,
                "currency" => $contact->currency,
                "account_number" => $contact->account_number,
                "swift_code" => $contact->swift_code,
                "created_at" => $contact->created_at,
                "updated_at" => $contact->updated_at
            ]
        ]);
    }

    // Generate UUID
    protected function generateUuid($contact)
    {
        $id = str_pad($contact->id, 3, 0, STR_PAD_LEFT);
        if ($contact->contactType->id == ContactType::VENDOR) {
            return ContactType::SHORT_VENDOR . $id;
        }

        return ContactType::SHORT_CLIENT . $id;
    }

    public function showByContactType(Request $request)
    {
        // inisiasi company/contact dalam bentuk query, supaya bisa dilakukan untuk filtering
        $query = Company::query();

        if ($request->has('contact_type')) {
            $query->where('contact_type_id', $request->contact_type);
        }

        // pembuatan kondisi ketika params search
        if ($request->has('search')) {
            // maka lakukan query bersarang seperti dibawah ini
            // $query->where(func...{}) => query akan berjalan jika kondisi didalamnya terpenuhi
            $query->where(function ($query) use ($request) {
                // query ini digunakan untuk filtering data
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('pic_name', 'like', "%$request->search%")
                    ->orWhere('phone', 'like', "%$request->search%")
                    ->orWhere('bank_name', 'like', "%$request->search%")
                    ->orWhere('account_name', 'like', "%$request->search%")
                    ->orWhere('account_number', 'like', "%$request->search%")
                    ->orWhereHas('contactType', function ($query) use ($request) { // query ini digunakan jika ada yang mencari ke arah relasinya, artinya sama seperti baris ke 26
                        $query->where('name', 'like', "%$request->search%");
                    });
            });
        }

        // keluaran dari index ini merupakan paginate
        $contacts = $query->paginate($request->per_page);

        // untuk index pengelolaan datanya terpisah file
        // untuk mempertahankan filtering bawaan paginate laravel
        // pembuatan file bisa menggunakan command `php artisan make:resource NamaFile`
        return new ContactCollection($contacts);
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        $contact = Company::find($id);
        if (!$contact) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            // Pastikan semua project yang terkait tidak terhapus, tetapi company_id-nya di-set NULL
            // Project::where('company_id', $id)->update(['company_id' => null]);

            // ProductCompanySpbProject::where('company_id', $id)->update(['company_id' => null]);

            // cek kondisi jika npwp / file tersedia, maka storage tersebut akan dihapus
            if ($contact->npwp) {
                Storage::delete($contact->npwp);
            }

            if ($contact->file) {
                Storage::delete($contact->file);
            }

            // Hapus contact
            $contact->delete();

            DB::commit();
            return MessageDakama::success("contact $contact->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
