<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            // The magic line that creates a two-way binding with the Livewire component's state.
            state: $wire.entangle('{{ $getStatePath() }}'),

            // A flag to prevent re-initialization on every Livewire update.
            initialized: false,

            // The function to initialize the TinyMCE editor.
            initializeEditor(el) {
                // Check if already initialized or if TinyMCE is not available yet.
                if (this.initialized || typeof tinymce === 'undefined') {
                    return
                }

                // Initialize TinyMCE
                tinymce.init({
                    target: el,
                    license_key: 'gpl',

                    // Pass configuration from our PHP class to the JS
                    height: {{ $getHeight() }},
                    menubar: {{ $getMenubar() ? 'true' : 'false' }},
                    plugins: '{{ $getPlugins() }}',
                    toolbar: '{{ $getToolbar() }}',
                    placeholder: '{{ $getPlaceholder() }}',

                    statusbar: false,
                    // Automatically handle dark mode based on Filament's theme
                    skin: document.documentElement.classList.contains('dark')
                        ? 'oxide-dark'
                        : 'oxide',
                    content_css: document.documentElement.classList.contains('dark')
                        ? 'dark'
                        : 'default',

                    // The most important part: syncing the editor's content with Filament's state.
                    setup: (editor) => {
                        // 1. When the editor is initialized, set its content to the current state.
                        editor.on('init', () => {
                            editor.setContent(this.state ?? '')
                        })

                        // 2. When the user types or changes content, update the state on blur.
                        editor.on('blur', () => {
                            this.state = editor.getContent()
                        })

                        // 3. If the state is updated from outside the editor (e.g., by another form field),
                        //    push that change back into the editor.
                        $watch('state', (newValue) => {
                            if (editor.getContent() !== newValue) {
                                editor.setContent(newValue ?? '')
                            }
                        })
                    },
                })

                this.initialized = true
            },
        }"
        x-init="initializeEditor($refs.tinymce)"
        {{-- IMPORTANT: This tells Livewire to ignore this DOM element during updates, --}}
        {{-- otherwise it will destroy and re-create the editor, losing all content. --}}
        wire:ignore
        {{ $attributes->merge($getExtraAttributes())->class(['filament-forms-rich-editor-component']) }}
    >
        {{-- The actual textarea that TinyMCE will attach to. It can be hidden. --}}
        <textarea x-ref="tinymce" class="hidden"></textarea>
    </div>
</x-dynamic-component>
