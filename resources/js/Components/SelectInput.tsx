import { forwardRef, SelectHTMLAttributes } from 'react';

export default forwardRef(function SelectInput(
    { className = '', children, ...props }: SelectHTMLAttributes<HTMLSelectElement>,
    ref: React.Ref<HTMLSelectElement>,
) {
    return (
        <select
            {...props}
            ref={ref}
            className={
                'h-11 rounded-lg border-tinta/15 bg-white text-tinta focus:border-ouro focus:ring-2 focus:ring-ouro/40 ' +
                className
            }
        >
            {children}
        </select>
    );
});
