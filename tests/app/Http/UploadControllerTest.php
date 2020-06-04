<?php

use App\Document;
use App\ProfilePicture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadControllerTest extends TestCase
{
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

    public function testBadProfileFormatFail()
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

    public function testUploadDocument()
    {
        Storage::fake('local_docs');

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [
                'name' => 'Document',
            ],
            [],
            [
                'file' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $documentId = json_decode($this->response->getContent())->_id;

        $document = Document::loadFromUuid($documentId);

        $this->assertEquals('Document', $document->title);
        $this->assertEquals('application/pdf', $document->mime);

        Storage::disk('local_docs')->assertMissing('document.pdf');
        Storage::disk('local_docs')->assertExists(sprintf('%s.pdf', $document->file_key));
    }

    public function testBigFileFail()
    {
        Storage::fake('docs');

        $file = UploadedFile::fake()->create('document.pdf', 50000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [
                'name' => 'Document',
            ],
            [],
            [
                'file' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(422);
        $message = json_decode($this->response->getContent())->file[0];
        $this->assertEquals('The file may not be greater than 12000 kilobytes.', $message);
    }

    public function testBadDocumentFormatFail()
    {
        Storage::fake('docs');

        $file = UploadedFile::fake()->create('document.rar', 5000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [
                'name' => 'Document',
            ],
            [],
            [
                'file' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(422);
        $message = json_decode($this->response->getContent())->file[0];
        $this->assertEquals('The file must be a file of type: pdf, jpg, png.', $message);
    }

    public function testDocumentAssignedToUser()
    {
        Storage::fake('docs');

        $file = UploadedFile::fake()->create('image.pdf');

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [
                'name' => 'Image',
            ],
            [],
            [
                'file' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $documentId = json_decode($this->response->getContent())->_id;

        $document = Document::loadFromUuid($documentId);

        $this->assertEquals($this->user->id, $document->user_id);
        $this->assertEquals($document->title, 'Image');
        $this->assertEquals($document->mime, 'application/pdf');
    }

    public function testDownloadDocument()
    {
        Storage::fake('docs');

        $file = UploadedFile::fake()->create('document.pdf', 700);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [
                'name' => 'Document',
            ],
            [],
            [
                'file' => $file,
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $documentId = json_decode($this->response->getContent())->_id;

        $this->actingAs($this->user)->call(
            'GET',
            sprintf('/api/download/document/%s', $documentId),
        );

        $this->assertResponseStatus(200);

        $disposition = $this->response->headers->get('content-disposition');
        $type = $this->response->headers->get('content-type');
        $this->assertEquals($disposition, 'attachment; filename=Document');
        $this->assertEquals($type, 'application/pdf');
    }

    public function testDownloadUnknownDocumentFailure()
    {
        $this->actingAs($this->user)->call(
            'GET',
            '/api/download/document/invalid'
        );

        $this->assertResponseStatus(422);
        $error = json_decode($this->response->getContent())->error;
        $this->assertEquals('Invalid Document ID', $error);
    }
}
