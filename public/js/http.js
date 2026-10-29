window.HF = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
        const hex = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
        return [hex.slice(0,8),hex.slice(8,12),hex.slice(12,16),hex.slice(16,20),hex.slice(20)].join('-');
    },
    async request(path, method = 'GET', data) {
        const response = await fetch(path, {
            method, credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf},
            ...(data === undefined ? {} : {body: JSON.stringify(data)})
        });
        let json = null;
        if (response.status !== 204) {
            try {json = await response.json();}
            catch {
                if (response.ok) throw new Error('El servidor devolvió una respuesta inesperada. Comprueba la lista antes de volver a guardar.');
                json = {};
            }
        }
        if (!response.ok) {
            const message = json?.errors ? Object.values(json.errors).flat().join(' ') :
                ({401:'Inicia sesión para continuar.',403:'No tienes permiso.',404:'No se encontró la dirección solicitada.',419:'La sesión venció. Recarga la página.',429:'Demasiados intentos. Espera un minuto.'}[response.status] || json?.message || 'No se pudo completar la operación.');
            const error = new Error(`HTTP ${response.status}: ${message}`); error.status = response.status; throw error;
        }
        if (json?.csrf_token) this.csrf = json.csrf_token;
        return json;
    }
};
