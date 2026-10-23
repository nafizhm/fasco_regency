<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ContentDocumentController extends Controller
{
    private function authorizeAccess(string $action = 'lihat'): void
    {
        abort_unless(DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'content-documents.index')
            ->where('hak_akses.lihat', 1)->where('hak_akses.'.$action, 1)->exists(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $categories = DB::table('content_documents')->selectRaw('category, COUNT(*) as total')->groupBy('category')->orderBy('category')->get();
        $request->validate(['category' => 'nullable|string|max:100', 'q' => 'nullable|string|max:255']);
        $documents = DB::table('content_documents')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($search) use ($request) {
                $term = '%'.$request->input('q').'%';
                $search->where('title', 'like', $term)->orWhere('original_name', 'like', $term);
            }))
            ->orderByDesc('id')->paginate(12)->withQueryString();
        return view('admin.content-documents.index', compact('categories', 'documents'));
    }

    public function begin(Request $request)
    {
        $this->authorizeAccess('tambah');
        $data = $request->validate(['title' => 'required|string|max:255', 'category' => 'required|string|max:100', 'name' => 'required|string|max:255', 'size' => 'required|integer|min:1']);
        $data['size'] = (int) $data['size'];
        $id = (string) Str::uuid();
        File::ensureDirectoryExists(storage_path('app/content-uploads'));
        File::put(storage_path('app/content-uploads/'.$id.'.json'), json_encode($data + ['user_id' => auth()->id()]));
        return response()->json(['id' => $id]);
    }

    public function chunk(Request $request, string $id)
    {
        $this->authorizeAccess('tambah');
        $base = $this->uploadPath($id);
        $metadata = json_decode(File::get($base.'.json'), true);
        abort_unless((string) $metadata['user_id'] === (string) auth()->id(), 403);
        $request->validate(['offset' => 'required|integer|min:0', 'chunk' => 'required|file|max:2048']);
        $handle = fopen($base.'.part', 'c+b');
        abort_unless($handle !== false, 500);
        try {
            flock($handle, LOCK_EX);
            $offset = (int) $request->input('offset');
            abort_if($offset > fstat($handle)['size'] || $offset + $request->file('chunk')->getSize() > $metadata['size'], 409);
            fseek($handle, $offset);
            $source = fopen($request->file('chunk')->getRealPath(), 'rb');
            try {
                $written = stream_copy_to_stream($source, $handle);
            } finally {
                fclose($source);
            }
            abort_unless($written === $request->file('chunk')->getSize(), 500, 'Gagal menulis file. Periksa kapasitas penyimpanan.');
            fflush($handle);
            return response()->json(['offset' => $offset + $written]);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function uploadPath(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);
        $base = storage_path('app/content-uploads/'.$id);
        abort_unless(is_file($base.'.json'), 404);
        return $base;
    }

    public function finish(string $id)
    {
        $this->authorizeAccess('tambah');
        $base = $this->uploadPath($id);
        $metadata = json_decode(File::get($base.'.json'), true);
        abort_unless((string) $metadata['user_id'] === (string) auth()->id(), 403);
        if (DB::table('content_documents')->where('path', $id)->exists()) {
            return response()->json(['message' => 'Konten berhasil diupload.']);
        }
        clearstatcache(true, $base.'.part');
        abort_unless(is_file($base.'.part') && filesize($base.'.part') === (int) $metadata['size'], 422, 'File belum selesai diupload.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($base.'.part');
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp', 'image/tiff', 'application/pdf',
            'video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/mpeg', 'video/x-ms-wmv', 'video/3gpp'];
        if (! in_array($mime, $allowed, true)) {
            File::delete($base.'.part', $base.'.json');
            abort(422, 'Gunakan file gambar, PDF, atau video yang didukung.');
        }
        File::ensureDirectoryExists(storage_path('app/content-documents'));
        DB::transaction(function () use ($base, $id, $metadata, $mime) {
            DB::table('content_documents')->insert(['title' => $metadata['title'], 'category' => trim($metadata['category']),
                'original_name' => basename($metadata['name']), 'path' => $id, 'mime' => $mime, 'size' => $metadata['size'], 'created_at' => now(), 'updated_at' => now()]);
            if (! rename($base.'.part', storage_path('app/content-documents/'.$id))) {
                throw new \RuntimeException('Gagal menyimpan konten.');
            }
        });
        return response()->json(['message' => 'Konten berhasil diupload.']);
    }

    public function download(Request $request, int $document)
    {
        $this->authorizeAccess();
        $file = DB::table('content_documents')->find($document);
        abort_unless($file, 404);
        if ($request->boolean('preview')) {
            return response()->file(storage_path('app/content-documents/'.$file->path),
                ['Content-Type' => $file->mime, 'X-Content-Type-Options' => 'nosniff']);
        }
        return response()->download(storage_path('app/content-documents/'.$file->path), $file->original_name,
            ['Content-Type' => $file->mime, 'X-Content-Type-Options' => 'nosniff']);
    }
}
