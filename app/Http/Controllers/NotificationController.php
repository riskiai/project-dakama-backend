<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Http\Resources\NotificationResource;
use App\Models\AttendanceAdjustment;
use App\Models\EmployeeLoan;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paginate' => 'string|in:true,false',
            'sort_by' => 'string',
            'sort_direction' => 'string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $query = Notification::query();

        $query->with([
            'requestBy',
            'recipients'
        ]);

        $query->when($request->has('category') && $request->filled('category'), function ($query) use ($request) {
            if (strtolower($request->category) == "overtime") {
                $query->where('notifiable_type', Overtime::class);
            } elseif (strtolower($request->category) == "payroll") {
                $query->where('notifiable_type', Payroll::class);
            } elseif (strtolower($request->category) ==  "loan") {
                $query->where('notifiable_type', EmployeeLoan::class);
            } elseif (strtolower($request->category) == "adjustment") {
                $query->where('notifiable_type', AttendanceAdjustment::class);
            }
        });

        $query->when($request->has('search') && $request->filled('search'), function ($query) use ($request) {
            $query->whereHas('requestBy', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            });
        });

        $query->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

        if ($request->has('paginate') && $request->filled('paginate') && $request->paginate == 'true') {
            $notifications = $query->paginate($request->per_page);
        } else {
            $notifications = $query->get();
        }

        return NotificationResource::collection($notifications);
    }

    public function show($id)
    {
        DB::beginTransaction();

        $notification = Notification::find($id);
        if (!$notification) {
            return MessageDakama::notFound("Notification not found");
        }

        $user = Auth::user();

        try {
            $recipient =  NotificationRecipient::where([
                'notification_id' => $notification->id,
                'role_id' => $user->role_id,
                'read_at' => null
            ])
                ->first();

            if ($recipient) {
                $recipient->update([
                    'read_at' => now(),
                    'read_by' => $user->name
                ]);

                $message = "Notification has been marked as read by $user->name";
            } else {
                $message = "Notification already marked as read by $user->name";
            }

            DB::commit();
            return MessageDakama::success($message, new NotificationResource($notification));
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        $notifications = Notification::whereBetween('created_at', [$request->start_date, $request->end_date]);
        if ($notifications->count() < 1) {
            return MessageDakama::warning("Notifikasi tidak ada diantara tanggal tersebut!");
        }

        try {
            $notifications->delete();

            DB::commit();
            return MessageDakama::success("Berhasil menghapus notifikasi");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal menghapus notifikasi" . $th->getMessage());
        }
    }
}
