import { useState, useEffect } from 'react';
import type { Provincia, Localidad } from '@/types/cotizador';

interface UseProvinciaLocalidadReturn {
    provincias: Provincia[];
    localidades: Localidad[];
    loading: boolean;
    loadingLocalidades: boolean;
    cargarLocalidades: (provinciaId: number) => void;
    limpiarLocalidades: () => void;
}

/**
 * Hook para cargar provincias y localidades desde la API pública.
 * Reutilizable en sección Origen y Destino del cotizador.
 */
export function useProvinciaLocalidad(): UseProvinciaLocalidadReturn {
    const [provincias, setProvincias] = useState<Provincia[]>([]);
    const [localidades, setLocalidades] = useState<Localidad[]>([]);
    const [loading, setLoading] = useState(false);
    const [loadingLocalidades, setLoadingLocalidades] = useState(false);

    useEffect(() => {
        setLoading(true);
        fetch('/api/publica/provincias')
            .then((r) => r.json())
            .then((data: Provincia[]) => setProvincias(data))
            .catch(console.error)
            .finally(() => setLoading(false));
    }, []);

    const cargarLocalidades = (provinciaId: number) => {
        setLoadingLocalidades(true);
        setLocalidades([]);
        fetch(`/api/publica/provincias/${provinciaId}/localidades`)
            .then((r) => r.json())
            .then((data: Localidad[]) => setLocalidades(data))
            .catch(console.error)
            .finally(() => setLoadingLocalidades(false));
    };

    const limpiarLocalidades = () => setLocalidades([]);

    return { provincias, localidades, loading, loadingLocalidades, cargarLocalidades, limpiarLocalidades };
}
