<?php

namespace App\Livewire;

use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\UnauthenticatedException;
use GuzzleHttp\Psr7\Utils;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The template files on the master: browse, upload (e.g. a server.jar), edit text files, create folders and
 * variants, delete. Changes reach the wrappers through the template sync within seconds.
 */
#[Title('Templates')]
class Templates extends CloudComponent
{
    use WithFileUploads;

    /**
     * Text files up to this size open in the editor; larger ones can only be downloaded.
     */
    public const EDITABLE_BYTES = 512 * 1024;

    #[Url]
    public string $group = '';

    #[Url]
    public string $variant = '';

    #[Url]
    public string $path = '';

    /**
     * File open in the editor, relative to the template.
     */
    public ?string $editing = null;

    public string $content = '';

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $newFolder = '';

    public string $newVariant = '';

    public function mount(): void
    {
        if ($this->group === '' || $this->variant === '') {
            $first = $this->load(fn ($cloud) => $cloud->templates())[0] ?? null;
            if ($first) {
                $this->group = $first['group'];
                $this->variant = $first['variant'];
            }
        }
    }

    public function select(string $template): void
    {
        [$this->group, $this->variant] = explode('/', $template, 2) + ['', ''];
        $this->path = '';
        $this->editing = null;
    }

    public function open(string $name): void
    {
        $this->path = $this->child($name);
    }

    public function up(): void
    {
        $this->path = str_contains($this->path, '/') ? substr($this->path, 0, strrpos($this->path, '/')) : '';
    }

    public function edit(string $name): void
    {
        $file = $this->child($name);
        try {
            $response = $this->cloud()->downloadTemplateFile($this->group, $this->variant, $file);
            $body = $response->body();
        } catch (UnauthenticatedException) {
            $this->expireSession();

            return;
        } catch (ApiException $e) {
            $this->notify($e->getMessage(), 'error');

            return;
        }
        if (strlen($body) > self::EDITABLE_BYTES || ! mb_check_encoding($body, 'UTF-8') || str_contains($body, "\0")) {
            $this->notify("{$name} is not a text file to edit here; download it instead.", 'error');

            return;
        }
        $this->editing = $file;
        $this->content = $body;
    }

    public function saveFile(): void
    {
        if ($this->editing === null) {
            return;
        }
        if ($this->attempt(fn ($cloud) => $cloud->writeTemplateFile($this->group, $this->variant, $this->editing, $this->content),
            "Saved {$this->editing}. The wrappers get it within seconds.")) {
            $this->editing = null;
        }
    }

    public function closeEditor(): void
    {
        $this->editing = null;
    }

    public function updatedUpload(): void
    {
        $this->validate(['upload' => 'required|file|max:524288']);
        $file = $this->child($this->upload->getClientOriginalName());
        // Streamed from the temporary file; the HTTP client closes it
        $stream = Utils::streamFor(Utils::tryFopen($this->upload->getRealPath(), 'r'));
        try {
            $this->attempt(fn ($cloud) => $cloud->writeTemplateFile($this->group, $this->variant, $file, $stream),
                "Uploaded {$file}. The wrappers get it within seconds.");
        } finally {
            $this->upload->delete();
            $this->upload = null;
        }
    }

    public function createFolder(): void
    {
        // Nested folders are created at once, e.g. "plugins/LuckPerms"
        $this->newFolder = trim(str_replace('\\', '/', $this->newFolder), " \t/");
        $this->validate(['newFolder' => ['required', 'string', 'max:200', 'regex:/^(?!.*(^|\/)\.{0,2}(\/|$))[^\/]+(\/[^\/]+)*$/']],
            ['newFolder.regex' => 'Folder names separated by /, without "." or "..".']);
        $folder = $this->child($this->newFolder);
        if ($this->attempt(fn ($cloud) => $cloud->createTemplateFolder($this->group, $this->variant, $folder),
            "Created {$folder}.")) {
            $this->newFolder = '';
        }
    }

    public function createVariant(): void
    {
        $this->validate(['newVariant' => ['required', 'regex:/^[A-Za-z0-9_-]{1,32}$/']],
            ['newVariant.regex' => 'Letters, numbers, - and _ (max. 32).']);
        if ($this->attempt(fn ($cloud) => $cloud->createTemplateFolder($this->group, $this->newVariant, '.'),
            "Created the variant {$this->newVariant}. Each new server of {$this->group} gets one variant at random.")) {
            $this->variant = $this->newVariant;
            $this->path = '';
            $this->newVariant = '';
        }
    }

    public function delete(string $name): void
    {
        $file = $this->child($name);
        $this->attempt(fn ($cloud) => $cloud->deleteTemplateFile($this->group, $this->variant, $file), "Deleted {$file}.");
    }

    public function deleteVariant(): void
    {
        if ($this->attempt(fn ($cloud) => $cloud->deleteTemplateFile($this->group, $this->variant, ''),
            "Deleted the variant {$this->variant}.")) {
            $this->variant = 'default';
            $this->path = '';
        }
    }

    private function child(string $name): string
    {
        return ltrim($this->path.'/'.$name, '/');
    }

    public function render()
    {
        $templates = $this->load(fn ($cloud) => $cloud->templates());
        $entries = $this->group === '' ? [] : $this->load(fn ($cloud) => $cloud->templateFiles($this->group, $this->variant, $this->path));

        return view('livewire.templates', [
            'templates' => collect($templates)->groupBy('group'),
            'entries' => $entries,
            'isGlobal' => $this->group === 'global',
            'formatSize' => fn (int $bytes) => match (true) {
                $bytes >= 1_073_741_824 => number_format($bytes / 1_073_741_824, 1).' GB',
                $bytes >= 1_048_576 => number_format($bytes / 1_048_576, 1).' MB',
                $bytes >= 1024 => number_format($bytes / 1024, 1).' KB',
                default => $bytes.' B',
            },
        ]);
    }
}
