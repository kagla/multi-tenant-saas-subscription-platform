<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class TenantStyle extends Component
{
    public string $primaryColor;
    public string $secondaryColor;
    public ?string $logoUrl;

    public function __construct()
    {
        $tenant = tenant();

        $this->primaryColor = $tenant?->primary_color ?? '#3B82F6';
        $this->secondaryColor = $tenant?->secondary_color ?? '#10B981';
        $this->logoUrl = $tenant?->logo_path ? asset('storage/' . $tenant->logo_path) : null;
    }

    public function render(): View
    {
        return view('components.tenant-style');
    }
}
