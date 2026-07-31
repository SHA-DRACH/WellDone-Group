<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Centralised, validated file handling for the CMS.
 *
 * Two disks are used deliberately:
 *   - `public`  — images and documents meant to be served on the website
 *   - `local`   — private uploads (résumés, quote attachments) that must only
 *                 be reachable through an authorised controller action
 *
 * Filenames are always regenerated. A user-supplied name is never used on
 * disk, which removes path traversal and double-extension tricks in one go.
 */
class MediaUploader
{
    /** Extensions we will store as images, whatever the client claims. */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'];

    /**
     * Store an image on the public disk, resizing it down to a sensible
     * maximum so an unedited phone photo cannot become a 6 MB page asset.
     */
    public function image(
        UploadedFile $file,
        string $directory,
        ?int $maxWidth = 1920,
        ?string $replacing = null,
    ): string {
        $extension = $this->safeExtension($file, self::IMAGE_EXTENSIONS);
        $path = trim($directory, '/').'/'.$this->filename($file, $extension);

        // SVGs are vector — nothing to resize, and rasterising would destroy
        // them. They are stored as-is; upload permission is the control.
        if ($extension === 'svg') {
            Storage::disk('public')->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );
        } else {
            // Intervention Image 4.2 renamed read() to decodePath() and
            // encodeByExtension() to encodeUsingFileExtension().
            $image = Image::decodePath($file->getRealPath());

            if ($maxWidth && $image->width() > $maxWidth) {
                $image->scaleDown(width: $maxWidth);
            }

            Storage::disk('public')->put(
                $path,
                (string) $image->encodeUsingFileExtension($extension, quality: 82)
            );
        }

        $this->deleteFrom('public', $replacing);

        return $path;
    }

    /** Store a publicly downloadable document (brochures, catalogues). */
    public function document(UploadedFile $file, string $directory, ?string $replacing = null): string
    {
        $extension = $this->safeExtension($file, array_merge(self::DOCUMENT_EXTENSIONS, self::IMAGE_EXTENSIONS));
        $path = trim($directory, '/').'/'.$this->filename($file, $extension);

        Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

        $this->deleteFrom('public', $replacing);

        return $path;
    }

    /**
     * Store a private upload — résumés and quote attachments. These live
     * outside the web root and are streamed by an authorised controller.
     */
    public function privateFile(UploadedFile $file, string $directory): string
    {
        $extension = $this->safeExtension($file, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'xls', 'xlsx']);
        $path = trim($directory, '/').'/'.$this->filename($file, $extension);

        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        return $path;
    }

    public function deleteFrom(string $disk, ?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '//'])) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    /**
     * Derive the extension from the file's actual contents rather than the
     * name the browser supplied, and reject anything not allow-listed.
     */
    protected function safeExtension(UploadedFile $file, array $allowed): string
    {
        // guessExtension() reads the MIME type detected from the file itself.
        $extension = Str::lower($file->guessExtension() ?: $file->getClientOriginalExtension());

        $extension = match ($extension) {
            'jpeg' => 'jpg',
            'tif' => 'tiff',
            default => $extension,
        };

        abort_unless(
            in_array($extension, array_map(fn ($e) => $e === 'jpeg' ? 'jpg' : $e, $allowed), true),
            422,
            'That file type is not accepted.'
        );

        return $extension;
    }

    protected function filename(UploadedFile $file, string $extension): string
    {
        $stem = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $stem = Str::limit($stem, 60, '') ?: 'file';

        return $stem.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$extension;
    }
}
