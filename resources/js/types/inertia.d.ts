import '@inertiajs/core';

// Props compartilhadas pelo HandleInertiaRequests (mantenha os dois lados em sincronia).
declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            app: { nome: string };
            auth: { usuario: { nome: string } | null };
            flash: { sucesso: string | null; erro: string | null };
        };
    }
}
