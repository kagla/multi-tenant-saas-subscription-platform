<style>
    :root {
        --primary-color: {{ $primaryColor }};
        --secondary-color: {{ $secondaryColor }};
    }

    /* Override Tailwind utilities with tenant branding */
    .bg-tenant-primary { background-color: var(--primary-color) !important; }
    .bg-tenant-secondary { background-color: var(--secondary-color) !important; }
    .text-tenant-primary { color: var(--primary-color) !important; }
    .text-tenant-secondary { color: var(--secondary-color) !important; }
    .border-tenant-primary { border-color: var(--primary-color) !important; }
    .ring-tenant-primary { --tw-ring-color: var(--primary-color) !important; }

    /* Apply to common UI elements */
    .btn-tenant-primary {
        background-color: var(--primary-color);
        color: #fff;
    }
    .btn-tenant-primary:hover {
        filter: brightness(0.9);
    }
</style>
