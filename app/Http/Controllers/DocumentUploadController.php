<?php

namespace App\Http\Controllers;

use App\Document;
use App\ValidationTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Webpatser\Uuid\Uuid;

class DocumentUploadController extends Controller
{
    use ValidationTrait;

    public function uploadDocument(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file|mimes:pdf,jpg,png|max:12000',
            'name' => 'required|string|min:3|max:255',
        ]);

        $file = $request->file('file');
        $name = $request->file('name');
        $uuid = Uuid::generate(4)->string;

        $filename = sprintf('/%s.%s', $uuid, $file->extension());

        Storage::disk($this->getDocumentStorage())->putFileAs(
            '',
            $file,
            $filename
        );

        $document = Document::create([
            '_id'        => Uuid::generate(4)->string,
            'title'      => $name,
            'file_url'   => $filename,
            'file_key'   => $uuid,
            'mime'       => $file->getMimeType(),
            'private'    => true,
            'user_id'    => Auth::user()->id,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
        ]);

        Cache::tags([
            'assets',
        ])->flush();

        return response()->json($upload, 201);
    }

    /**
     * Get the location of the documents based on the environment.
     *
     * @return string The disk to retrieve the document from.
     */
    private function getDocumentStorage()
    {
        if (env('APP_ENV') === 'production' || env('APP_ENV') === 'staging') {
            return 'cloud_docs';
        }

        return 'local_docs';
    }
}
