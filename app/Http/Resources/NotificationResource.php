<?php

namespace App\Http\Resources;

use App\Models\AttendanceAdjustment;
use App\Models\EmployeeLoan;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return [
            "id" => $this->id,
            "request_by" => [
                "user_id" => $this->from_user_id,
                "name" => $this->requestBy->name,
            ],
            "category" => $this->getCategory($this->notifiable_type),
            "title" => $this->title,
            "description" => $this->description,
            "detail" => $this->notifiable,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "read_by" => $this->getRecipient($this->recipients()),
        ];
    }

    protected function getRecipient($recipients)
    {
        $user = Auth::user();

        if ($user->role_id === Role::ADMIN) {
            return $recipients->where('role_id', Role::OWNER)->first(['read_by as name', 'read_at']);
        }

        if ($user->role_id === Role::SUPERVISOR) {
            return $recipients->where('role_id', Role::ADMIN)->orWhere('role_id', Role::OWNER)->first(['read_by as name', 'read_at']);
        }

        return $recipients->where('role_id', $user->role_id)->first(['read_by as name', 'read_at']);
    }

    protected function getCategory($type)
    {
        if ($type == Overtime::class) {
            $category = "Overtime";
        } elseif ($type == Payroll::class) {
            $category = "Payroll";
        } elseif ($type == EmployeeLoan::class) {
            $category = "Loan";
        } elseif ($type == AttendanceAdjustment::class) {
            $category = "Adjustment";
        } else {
            $category = "Unknown";
        }

        return strtoupper($category);
    }
}
