import { useSyncExternalStore } from 'react';

/** Contador global de notificaciones no leídas (compartido entre sidebar, listener y página). */
let count = 0;
const listeners = new Set<() => void>();

export const notificacionesStore = {
    get: () => count,
    set(n: number) {
        const nuevo = Math.max(0, n);
        if (nuevo === count) return;
        count = nuevo;
        listeners.forEach((l) => l());
    },
    increment() {
        this.set(count + 1);
    },
    subscribe(l: () => void) {
        listeners.add(l);
        return () => listeners.delete(l);
    },
};

export function useNotificacionesNoLeidas(): number {
    return useSyncExternalStore(notificacionesStore.subscribe, notificacionesStore.get, () => 0);
}
