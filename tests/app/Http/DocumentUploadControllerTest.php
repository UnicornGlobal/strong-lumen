<?php

use App\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Testing\DatabaseTransactions;

class DocumentUploadControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUploadDocument()
    {
        Storage::fake('uploads');

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [],
            [],
            [
                'file' => $file,
                'name' => 'Document',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $upload_id = json_decode($this->response->getContent())->_id;

        $document = Document::loadFromUuid($upload_id);

        $this->assertEquals('Document', $document->title);
        $this->assertEquals('application/pdf', $upload->mime);
    }

    public function testBigFileFail()
    {
        Storage::fake('uploads');

        $file = UploadedFile::fake()->create('document.pdf', 50000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [],
            [],
            [
                'file' => $file,
                'name' => 'Document',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(422);
        $message = json_decode($this->response->getContent())->file[0];
        $this->assertEquals('The file may not be greater than 12000 kilobytes.', $message);
    }

    public function testBadFormatFail()
    {
        Storage::fake('uploads');

        $file = UploadedFile::fake()->create('document.rar', 5000);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [],
            [],
            [
                'file' => $file,
                'name' => 'Document',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(422);
        $message = json_decode($this->response->getContent())->file[0];
        $this->assertEquals('The file must be a file of type: pdf.', $message);
    }

    public function testDocumentAssignedToUser()
    {
        Storage::fake('uploads');

        $file = UploadedFile::fake()->create('image.png', 200);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [],
            [],
            [
                'file' => $file,
                'name' => 'Image',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $documentId = json_decode($this->response->getContent())->_id;

        $upload = Document::loadFromUuid($documentId);

        $this->assertEquals($this->user->id, $upload->user_id);
        $this->assertEquals($upload->title, 'Image');
        $this->assertEquals($upload->mime, 'image/png');
    }

    public function testDownloadDocument()
    {
        Storage::fake('uploads');

        $file = UploadedFile::fake()->create('document.pdf', 700);

        $this->actingAs($this->user)->call(
            'POST',
            '/api/upload/document',
            [],
            [],
            [
                'file' => $file,
                'name' => 'Download',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

        $this->assertResponseStatus(201);
        $documentId = json_decode($this->response->getContent())->_id;

        $this->actingAs($this->user)->call(
            'GET',
            sprintf('/api/download/%s', $documentId),
        );

        $this->assertResponseStatus(200);

        $disposition = $this->response->headers->get('content-disposition');
        $type = $this->response->headers->get('content-type');
        $this->assertEquals($disposition, 'attachment; filename="Download"');
        $this->assertEquals($type, 'application/pdf');
    }

    public function testDownloadUnknownDocumentFailure()
    {
        $this->actingAs($this->user)->call(
            'GET',
            '/api/download/invalid'
        );

        $this->assertResponseStatus(500);
        $error = json_decode($this->response->getContent())->error;
        $this->assertEquals('Invalid Document ID', $error);
    }
}
