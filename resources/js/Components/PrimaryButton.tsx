import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                'inline-flex h-11 items-center justify-center rounded-lg border border-transparent bg-tinta px-5 text-sm font-semibold text-papel transition duration-150 ease-in-out hover:bg-tinta-claro focus:outline-none focus:ring-2 focus:ring-ouro focus:ring-offset-2 focus:ring-offset-papel disabled:cursor-not-allowed disabled:opacity-40 ' +
                className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
