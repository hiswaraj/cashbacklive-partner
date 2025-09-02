<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Contracts;
use Filament\Forms\Components\Field;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;

final class TinyMCE extends Field implements Contracts\CanBeLengthConstrained
{
    use Concerns\CanBeLengthConstrained;
    use Concerns\HasPlaceholder;

    protected string $view = 'forms.components.tinymce';

    // Default editor configuration
    protected string|Closure $plugins = 'lists';

    protected string|Closure $toolbar = 'blocks | bold strikethrough underline | bullist numlist outdent indent | forecolor backcolor';

    protected bool|Closure $menubar = false;

    protected string|Closure $height = '300';

    protected function setUp(): void
    {
        parent::setUp();

        // This is the key change: Register the JS asset using a path
        // that Vite will create for us.
        FilamentAsset::register([
            Js::make('tinymce', asset('build/tinymce/tinymce.min.js')),
        ], 'local/filament-tinyemce');
    }

    // Chainable methods to allow for easy configuration
    public function plugins(string|Closure $plugins): static
    {
        $this->plugins = $plugins;

        return $this;
    }

    public function toolbar(string|Closure $toolbar): static
    {
        $this->toolbar = $toolbar;

        return $this;
    }

    public function menubar(bool|Closure $menubar = true): static
    {
        $this->menubar = $menubar;

        return $this;
    }

    public function height(string|Closure|null $height): static
    {
        $this->height = $height;

        return $this;
    }

    // Getters to pass the evaluated values to the Blade view
    public function getPlugins(): string
    {
        return $this->evaluate($this->plugins);
    }

    public function getToolbar(): string
    {
        return $this->evaluate($this->toolbar);
    }

    public function getMenubar(): bool
    {
        return $this->evaluate($this->menubar);
    }

    public function getHeight(): ?string
    {
        return $this->evaluate($this->height);
    }
}
