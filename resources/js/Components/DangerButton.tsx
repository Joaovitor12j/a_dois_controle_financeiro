import { ButtonHTMLAttributes } from 'react';

export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                'inline-flex h-11 items-center justify-center rounded-lg border border-transparent bg-vinho px-5 text-sm font-semibold text-papel transition duration-150 ease-in-out hover:bg-vinho-escuro focus:outline-none focus:ring-2 focus:ring-vinho focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 ' +
                className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
