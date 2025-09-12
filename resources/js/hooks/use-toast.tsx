// hooks/useToast.ts
import { useEffect } from 'react';
import { toast } from 'sonner';
import { usePage } from '@inertiajs/react';

export const useToast = () => {
    const { props } = usePage<{ success?: { message: string } }>();

    useEffect(() => {
        if (props.success) {

            const { message } = props.success;
            // Mostrar toast
            toast.success(message, {
                position: 'top-center',
                duration: 2000,
                richColors: true,
            });

            // Opcional: Limpiar el toast después de mostrarlo
            // Esto evita que se muestre de nuevo al navegar
            window.history.replaceState(
                { ...window.history.state, props: { ...window.history.state?.props, toast: undefined } },
                ''
            );
        }
    }, [props.toast]);
};
