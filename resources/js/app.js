import './bootstrap';

// Prevent up/down arrows on number inputs using Alpine
document.addEventListener('alpine:init', () => {
    Alpine.store('utils', {
        init() {
            document.querySelectorAll('input[type=number]').forEach((input) => {
                input.addEventListener('keydown', (event) => {
                    if (event.keyCode === 38 || event.keyCode === 40) {
                        event.preventDefault();
                    }
                });
            });
        },
    });
});
