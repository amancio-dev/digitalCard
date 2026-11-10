import Alpine from 'alpinejs';

window.Alpine = Alpine;

function applyStoredColorScheme() {
    const meta = document.querySelector('meta[name="color-scheme"]');
    if (!meta) return;
    try {
        const stored = localStorage.getItem('color-scheme');
        meta.content = stored === 'light' || stored === 'dark' ? stored : 'light dark';
    } catch {
        meta.content = 'light dark';
    }
}

window.toggleColorScheme = function toggleColorScheme() {
    const meta = document.querySelector('meta[name="color-scheme"]');
    if (!meta) return;

    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const systemScheme = systemPrefersDark ? 'dark' : 'light';

    let stored = null;
    try {
        stored = localStorage.getItem('color-scheme');
    } catch {}

    const currentRendered = stored === 'light' || stored === 'dark' ? stored : systemScheme;
    const targetScheme = currentRendered === 'dark' ? 'light' : 'dark';

    try {
        if (targetScheme === systemScheme) {
            localStorage.removeItem('color-scheme');
            meta.content = 'light dark';
        } else {
            localStorage.setItem('color-scheme', targetScheme);
            meta.content = targetScheme;
        }
    } catch {
        meta.content = targetScheme === systemScheme ? 'light dark' : targetScheme;
    }
};

window.addEventListener('storage', (event) => {
    if (event.key === 'color-scheme') {
        applyStoredColorScheme();
    }
});

Alpine.start();
