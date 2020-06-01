<?php

namespace App\Http\Controllers;

use App\ProfilePicture;
use App\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Webpatser\Uuid\Uuid;

class ImageUploadController extends Controller
{
    use ValidationTrait;

    /**
     * Adds a profile photo for a user.
     *
     * @param Request $request The request as received.
     *
     * @return JsonResponse
     */
    public function setUserProfilePhoto(Request $request)
    {
        $this->validate(
            $request, [
                'photo' => 'required|image|file|mimes:jpeg,png|dimensions:min_width=300,min_height=300,max_height:7680,max_width:7680|max:5120',
            ]
        );

        $uuid = Uuid::generate(4)->string;

        $file = $request->file('photo');
        $filename = sprintf('%s.%s', $uuid, $file->extension());

        $image = Image::make($file);
        $image->fit(300);

        $url = sprintf('photo/%s', $filename);

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

        return response()->json($profilePicture, 201);
    }
}
