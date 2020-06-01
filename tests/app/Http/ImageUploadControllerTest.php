<?php

use App\ProfilePicture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ImageUploadControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testProfilePictureUpload()
    {
        Storage::fake('images');

        $file = UploadedFile::fake()->image('profile.png', 4000, 2000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/profile',
            [],
            [],
            [
                'picture' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $_id = json_decode($this->response->getContent())->_id;

        $image = ProfilePicture::loadFromUuid($_id);

        $this->assertEquals($this->user->first_name, $image->title);
        $this->assertEquals('image/png', $image->mime);
    }

    public function testProfilePictureUrl()
    {
        Storage::fake('images');

        $file = UploadedFile::fake()->image('profile.png', 4000, 2000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/profile',
            [],
            [],
            [
                'picture' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $_id = json_decode($this->response->getContent())->_id;

        $image = ProfilePicture::loadFromUuid($_id);
        $image->file_url = 'https://example.com';
        $image->save();
        $image->refresh();

        $this->assertEquals('https://example.com', $image->url);
    }

    public function testSmallImageFail()
    {
        Storage::fake('images');

        $file = UploadedFile::fake()->image('profile.png', 20, 20);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/profile',
            [],
            [],
            [
                'picture' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(422);
        $message = json_decode($this->response->getContent())->picture[0];
        $this->assertEquals('The picture has invalid image dimensions.', $message);
    }

    public function testBadFormatFail()
    {
        Storage::fake('images');

        $file = UploadedFile::fake()->image('profile.zip');

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/profile',
            [],
            [],
            [
                'picture' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(422);
        $message = json_decode($this->response->getContent())->picture[0];
        $this->assertEquals('The picture must be an image.', $message);
    }

    public function testPictureAssignedToUser()
    {
        Storage::fake('images');

        $file = UploadedFile::fake()->image('profile.jpg', 1024, 768);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/profile',
            [],
            [],
            [
                'picture' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);

        $_id = json_decode($this->response->getContent())->_id;

        $image = ProfilePicture::loadFromUuid($_id);

        $this->user->refresh();

        $this->assertEquals($this->user->profile_picture_id, $image->id);
        $this->assertEquals($this->user->profile_picture->title, $this->user->first_name);
        $this->assertEquals($this->user->profile_picture->mime, 'image/jpeg');
    }
}
