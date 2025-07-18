<?php

namespace App\Jobs;

use App\Mail\SendApprovalMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailApprovalJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    public function __construct(
        public $target,
        public $user,
        public $data
    ) {}

    public function handle(): void
    {
        Log::info('Mengirim ke: ' . implode(',', $this->target));
        foreach ($this->target as $email) {
            Mail::to($email)->send(new SendApprovalMail($this->user, $this->data));
        }
    }
}
