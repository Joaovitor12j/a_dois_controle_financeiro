import { ReactNode } from 'react';

export default function BlocoCondicional({
    aberto,
    children,
}: {
    aberto: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={`grid transition-all duration-300 ease-in-out ${
                aberto ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'
            }`}
        >
            <div className="overflow-hidden">{children}</div>
        </div>
    );
}
