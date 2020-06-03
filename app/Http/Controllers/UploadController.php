<?php

namespace App\Http\Controllers;

use App\Document;
use App\ProfilePicture;
use App\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Webpatser\Uuid\Uuid;

class UploadController extends Controller
{
    use ValidationTrait;

    public function setUserProfilePicture(Request $request)
    {
        $this->validate(
            $request,
            [
                'picture' => 'required|image|file|mimes:jpeg,png|dimensions:min_width=300,min_height=300,max_height:7680,max_width:7680|max:5120',
            ]
        );

        $uuid = Uuid::generate(4)->string;

        $file = $request->file('picture');
        $filename = sprintf('%s.%s', $uuid, $file->extension());

        $image = Image::make($file);
        $image->fit(300);

        $url = sprintf('picture/%s', $filename);

        Storage::put(
            $url,
            $image->encode()
        );

        Storage::setVisibility(
            $url,
            'public'
        );

        $profilePicture = ProfilePicture::create([
            '_id'        => Uuid::generate(4)->string,
            'user_id'    => Auth::user()->id,
            'title'      => Auth::user()->first_name,
            'mime'       => $file->getMimeType(),
            'file_url'   => Storage::url($url),
            'file_key'   => $uuid,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
        ]);

        Auth::user()->profile_picture_id = $profilePicture->id;
        Auth::user()->save();

        Cache::tags([
            'users',
            'assets',
        ])->flush();

        return response()->json($profilePicture, 201);
    }

    public function uploadDocument(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file|mimes:pdf,jpg,png|max:12000',
            'name' => 'required|string|min:3|max:255',
        ]);

        $file = $request->file('file');
        $name = $request->input('name');

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

        return response()->json($document, 201);
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
