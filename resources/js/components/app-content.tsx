import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

export function AppContent({ variant = 'sidebar', children, className, ...props }: Props) {
    if (variant === 'sidebar') {
        return (
            <main
                className={`flex flex-1 flex-col min-h-svh ${className ?? ''}`}
                {...props}
            >
                {children}
            </main>
        );
    }

    return (
        <main
            className={`mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl ${className ?? ''}`}
            {...props}
        >
            {children}
        </main>
    );
}
