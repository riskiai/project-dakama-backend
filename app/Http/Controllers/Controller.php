<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailApprovalJob;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    protected function createNotification(Model $model, User $user, $title = "", $message = "")
    {
        $notification = $model->notification()->create([
            'from_user_id' => $user->id,
            'title' => $title,
            'description' => $message,
        ]);

        SendEmailApprovalJob::dispatch([$model->pic->email], $model->pic, $notification)->onQueue('mail')->delay(now()->addMinutes(1))->onConnection('database');

        $this->broadcastMessage($notification, $user);
    }

    protected function broadcastMessage(Notification $model, User $user)
    {
        if ($user->hasRole(Role::SUPERVISOR)) {
            $model->recipients()->insert([
                [
                    'notification_id' => $model->id,
                    'role_id' => Role::ADMIN,
                ],
                [
                    'notification_id' => $model->id,
                    'role_id' => Role::OWNER,
                ],
            ]);
        } elseif ($user->hasRole(Role::ADMIN)) {
            $model->recipients()->create(
                [
                    'notification_id' => $model->id,
                    'role_id' => Role::OWNER,
                ]
            );
        } elseif ($user->hasRole(Role::KARYAWAN)) {
            $model->recipients()->insert([
                [
                    'notification_id' => $model->id,
                    'role_id' => Role::SUPERVISOR,
                ],
                [
                    'notification_id' => $model->id,
                    'role_id' => Role::ADMIN,
                ],
                [
                    'notification_id' => $model->id,
                    'role_id' => Role::OWNER,
                ],
            ]);
        }
    }
}
