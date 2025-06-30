<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_belongs_to_user()
    {
        $user = User::factory()->create();
        $document = Document::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $document->user);
        $this->assertEquals($user->id, $document->user->id);
    }

    public function test_user_has_many_documents()
    {
        $user = User::factory()->create();
        Document::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->documents);
    }

    public function test_document_status_helpers()
    {
        $document = Document::factory()->completed()->create();
        $this->assertTrue($document->isCompleted());
        $this->assertFalse($document->isProcessing());
    }

    public function test_file_size_formatting()
    {
        $document = Document::factory()->create(['file_size' => 1048576]); // 1MB
        $this->assertEquals('1.00 MB', $document->file_size_formatted);
    }
}