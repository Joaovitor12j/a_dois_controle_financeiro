import { ButtonHTMLAttributes } from 'react';

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            type={type}
            className={
                'inline-flex h-11 items-center justify-center rounded-lg border border-tinta/15 bg-white px-5 text-sm font-medium text-tinta transition duration-150 ease-in-out hover:bg-papel-sombra focus:outline-none focus:ring-2 focus:ring-ouro focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 ' +
                className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
