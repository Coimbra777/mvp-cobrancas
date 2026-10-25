import { afterEach } from 'vitest';

// Componentes montados com attachTo ficam no body; limpa entre testes.
afterEach(() => {
    document.body.innerHTML = '';
});
