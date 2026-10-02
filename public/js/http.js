window.HF = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    async request(path, method = 'GET', data) {
        const response = await fetch(path, {
            method, credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf},
            ...(data === undefined ? {} : {body: JSON.stringify(data)})
        });
        const json = response.status === 204 ? null : await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = json.errors ? Object.values(json.errors).flat().join(' ') :
                ({401:'Inicia sesión para continuar.',403:'No tienes permiso.',419:'La sesión venció. Recarga la página.',429:'Demasiados intentos. Espera un minuto.'}[response.status] || json.message || 'No se pudo completar la operación.');
            const error = new Error(message); error.status = response.status; throw error;
        }
        if (json?.csrf_token) this.csrf = json.csrf_token;
        return json;
    }
};
