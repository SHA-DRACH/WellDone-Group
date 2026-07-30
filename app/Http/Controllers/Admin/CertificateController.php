<?php

namespace App\Http\Controllers\Admin;

use App\Models\Certificate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CertificateController extends BaseCrudController
{
    protected string $model = Certificate::class;

    protected string $viewPath = 'admin.certificates';

    protected string $routeName = 'certificates';

    protected string $label = 'Certificate';

    protected string $labelPlural = 'Certificates & Accreditations';

    protected ?string $subtitle = 'Registrations, licences and permits shown on the Certifications page.';

    protected array $searchable = ['title', 'issuer', 'reference_number'];

    protected array $imageFields = ['image' => 'certificates/images'];

    protected array $documentFields = ['file_path' => 'certificates/files'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'issuer' => ['nullable', 'string', 'max:180'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:800'],
            'image' => ['nullable', 'image', 'max:4096'],
            'file_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'title' => ['label' => 'Certificate', 'type' => 'title', 'sub' => 'issuer'],
            'reference_number' => ['label' => 'Reference'],
            'issued_at' => ['label' => 'Issued', 'type' => 'date', 'format' => 'M Y'],
            'expires_at' => ['label' => 'Expires', 'type' => 'date', 'format' => 'M Y'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Certificate title', 'required' => true, 'col' => 8],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'issuer' => ['label' => 'Issued by', 'col' => 6],
            'reference_number' => ['label' => 'Reference number', 'col' => 6],
            'issued_at' => ['label' => 'Issued on', 'input' => 'date', 'col' => 6],
            'expires_at' => ['label' => 'Valid until', 'input' => 'date', 'col' => 6,
                'help' => 'Leave blank if it does not expire. Expired certificates are flagged in red.'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 3],

            'image' => ['type' => 'image', 'label' => 'Preview image', 'panel' => 'side', 'preview' => 'image_url'],
            'file_path' => ['type' => 'file', 'label' => 'Certificate document', 'panel' => 'side',
                'accept' => '.pdf,.jpg,.jpeg,.png', 'help' => 'PDF or image, up to 10 MB.'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
