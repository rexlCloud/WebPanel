<?php

namespace Tests\Feature;

use App\Livewire\ServerDetail;
use App\Livewire\Templates;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\FakesCloud;
use Tests\TestCase;

class TemplatesTest extends TestCase
{
    use FakesCloud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAs();
    }

    private function assertSentTo(string $method, string $url): void
    {
        Http::assertSent(fn (Request $request) => $request->method() === $method && $request->url() === self::API.$url);
    }

    public function test_the_page_lists_templates_and_files(): void
    {
        $this->fakeCloud();

        Livewire::test(Templates::class)
            ->assertSet('group', 'Lobby')
            ->assertSet('variant', 'default')
            ->assertSee('map-desert')
            ->assertSee('server.properties')
            ->call('open', 'plugins')
            ->assertSet('path', 'plugins')
            ->call('up')
            ->assertSet('path', '');
        $this->assertSentTo('GET', '/templates/Lobby/default/files?path=plugins');
    }

    public function test_text_files_can_be_edited_and_saved(): void
    {
        $this->fakeCloud();

        Livewire::test(Templates::class)
            ->call('edit', 'server.properties')
            ->assertSet('editing', 'server.properties')
            ->assertSet('content', 'motd=Hello')
            ->set('content', 'motd=Changed')
            ->call('saveFile')
            ->assertSet('editing', null)
            ->assertDispatched('notify', type: 'success');

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && $request->url() === self::API.'/templates/Lobby/default/file?path=server.properties'
            && $request->body() === 'motd=Changed');
    }

    public function test_files_are_uploaded_into_the_current_folder(): void
    {
        $this->fakeCloud();

        Livewire::test(Templates::class)
            ->set('path', 'plugins')
            ->set('upload', UploadedFile::fake()->createWithContent('MyPlugin.jar', 'jar-bytes'))
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success');

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && $request->url() === self::API.'/templates/Lobby/default/file?path=plugins%2FMyPlugin.jar'
            && $request->body() === 'jar-bytes');
    }

    public function test_folders_variants_and_deleting(): void
    {
        $this->fakeCloud();

        Livewire::test(Templates::class)
            ->set('newFolder', 'world')
            ->call('createFolder')
            ->set('newVariant', 'map-forest')
            ->call('createVariant')
            ->assertSet('variant', 'map-forest')
            ->call('delete', 'server.properties')
            ->set('newVariant', '../evil')
            ->call('createVariant')
            ->assertHasErrors('newVariant');

        $this->assertSentTo('POST', '/templates/Lobby/default/folder?path=world');
        $this->assertSentTo('POST', '/templates/Lobby/map-forest/folder?path=.');
        $this->assertSentTo('DELETE', '/templates/Lobby/map-forest/file?path=server.properties');
    }

    public function test_empty_folders_and_templates_are_shown(): void
    {
        $this->fakeCloud([self::API.'/templates/*/files*' => Http::response([])]);

        Livewire::test(Templates::class)
            ->assertOk()
            ->assertSee('No files here yet')
            ->call('select', 'global/server')
            ->assertSet('group', 'global')
            ->assertSee('No files here yet')
            ->call('select', 'Lobby/default')
            ->call('open', 'world')
            ->assertSet('path', 'world')
            ->assertSee('No files here yet');
        $this->assertSentTo('GET', '/templates/global/server/files?path=');
        $this->assertSentTo('GET', '/templates/Lobby/default/files?path=world');
    }

    public function test_folders_are_created_in_the_current_folder_and_may_be_nested(): void
    {
        $this->fakeCloud();

        Livewire::test(Templates::class)
            ->call('open', 'plugins')
            ->set('newFolder', 'LuckPerms')
            ->call('createFolder')
            ->assertHasNoErrors()
            ->assertSet('newFolder', '')
            ->assertDispatched('notify', type: 'success')
            ->set('newFolder', '/WorldEdit/schematics/')
            ->call('createFolder')
            ->assertHasNoErrors()
            ->set('newFolder', '../evil')
            ->call('createFolder')
            ->assertHasErrors('newFolder')
            ->set('newFolder', 'a//b')
            ->call('createFolder')
            ->assertHasErrors('newFolder');

        $this->assertSentTo('POST', '/templates/Lobby/default/folder?path=plugins%2FLuckPerms');
        $this->assertSentTo('POST', '/templates/Lobby/default/folder?path=plugins%2FWorldEdit%2Fschematics');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'evil'));
    }

    public function test_files_are_downloaded_through_the_panel(): void
    {
        $this->fakeCloud();

        $response = $this->get(route('templates.download', ['group' => 'Lobby', 'variant' => 'default', 'path' => 'server.properties']));

        $response->assertOk();
        $this->assertSame('motd=Hello', $response->streamedContent());
        $this->assertStringContainsString('server.properties', $response->headers->get('Content-Disposition'));
    }

    public function test_a_running_server_can_be_saved_into_a_template(): void
    {
        $this->fakeCloud();

        Livewire::test(ServerDetail::class, ['name' => 'Lobby-1'])
            ->assertSee('Save to template')
            ->set('savingTemplate', true)
            ->set('saveVariant', 'map-desert')
            ->call('saveToTemplate')
            ->assertSet('savingTemplate', false);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === self::API.'/servers/Lobby-1/save-template'
            && $request->data() === ['variant' => 'map-desert']);
    }

    public function test_the_page_needs_the_permission(): void
    {
        $this->loginAs('viewer', ['SEE_ALL_SERVERS']);
        $this->fakeCloud(permissions: ['SEE_ALL_SERVERS']);

        $this->get(route('dashboard'))->assertDontSee(route('templates'));
    }
}
