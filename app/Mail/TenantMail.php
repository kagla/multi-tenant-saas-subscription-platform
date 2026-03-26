<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

abstract class TenantMail extends Mailable
{
    protected ?Tenant $tenant;

    public function __construct()
    {
        $this->tenant = tenant();
    }

    protected function tenantEnvelope(string $subject): Envelope
    {
        $tenant = $this->tenant;

        $from = null;
        if ($tenant && $tenant->email_from_address) {
            $from = new Address(
                $tenant->email_from_address,
                $tenant->email_from_name ?? $tenant->name
            );
        }

        return new Envelope(
            from: $from,
            subject: $subject,
        );
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function withTenant(Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
    }
}
