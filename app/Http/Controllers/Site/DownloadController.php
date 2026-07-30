<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function index(): View
    {
        seo()->set([
            'title' => 'Downloads — '.setting('company.name'),
            'description' => 'Company profile, capability statements, catalogues and other documents available for download.',
        ]);

        return view('site.downloads.index', [
            'groups' => Download::query()
                ->active()
                // A row with no uploaded file is a placeholder awaiting a
                // document; it should never appear on the public list.
                ->where('file_path', '!=', '')
                ->with('division:id,name,slug')
                ->ordered()
                ->get()
                ->groupBy('category'),
        ]);
    }

    /**
     * Files are streamed through this action rather than linked directly so
     * downloads can be counted and inactive documents stay unreachable.
     */
    public function download(Download $download): StreamedResponse
    {
        abort_unless($download->is_active && filled($download->file_path), 404);
        abort_unless(Storage::disk('public')->exists($download->file_path), 404);

        $download->recordDownload();

        return Storage::disk('public')->download(
            $download->file_path,
            $download->file_name ?: basename($download->file_path)
        );
    }
}
