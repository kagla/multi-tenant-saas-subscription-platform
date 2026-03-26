<?php

namespace App\Mail;

use App\Models\Invitation;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvitation extends TenantMail implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public Tenant $mailTenant,
    ) {
        parent::__construct();
        $this->tenant = $mailTenant;
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope("{$this->mailTenant->name}에 초대되었습니다");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-invitation',
            with: [
                'acceptUrl' => url("/invitations/{$this->invitation->token}/accept"),
                'tenantName' => $this->mailTenant->name,
                'role' => $this->invitation->role,
                'expiresAt' => $this->invitation->expires_at,
                'tenant' => $this->mailTenant,
            ],
        );
    }
}
