import {
    Popover,
    PopoverButton,
    PopoverPanel,
    Transition,
} from '@headlessui/react';
import { format, isValid, parse } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import { CalendarDays } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { DayPicker, getDefaultClassNames } from 'react-day-picker';
import 'react-day-picker/style.css';

const FORMATO_ISO = 'yyyy-MM-dd';
const FORMATO_EXIBICAO = 'dd/MM/yyyy';

function paraData(valorIso: string): Date | undefined {
    if (!valorIso) {
        return undefined;
    }

    const data = parse(valorIso, FORMATO_ISO, new Date());

    return isValid(data) ? data : undefined;
}

export default function DateInput({
    id,
    value,
    onChange,
    className = '',
    hasError = false,
    isFocused = false,
}: {
    id?: string;
    value: string;
    onChange: (valorIso: string) => void;
    className?: string;
    hasError?: boolean;
    isFocused?: boolean;
}) {
    const dataSelecionada = paraData(value);
    const defaultClassNames = getDefaultClassNames();
    const botaoRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (isFocused) {
            botaoRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <Popover className={'relative ' + className}>
            <PopoverButton
                id={id}
                ref={botaoRef}
                type="button"
                className={
                    'flex h-11 w-full items-center justify-between rounded-lg border bg-white px-3 text-left text-tinta focus:outline-none focus:ring-2 focus:ring-ouro/40 ' +
                    (hasError
                        ? 'border-vinho/60 focus:border-vinho'
                        : 'border-tinta/15 focus:border-ouro')
                }
            >
                <span
                    className={dataSelecionada ? '' : 'text-tinta/30'}
                >
                    {dataSelecionada
                        ? format(dataSelecionada, FORMATO_EXIBICAO)
                        : 'dd/mm/aaaa'}
                </span>
                <CalendarDays
                    className="h-4 w-4 shrink-0 text-tinta/40"
                    aria-hidden
                />
            </PopoverButton>

            <Transition
                enter="transition ease-out duration-100"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
            >
                <PopoverPanel
                    anchor={{ to: 'bottom start', gap: 8 }}
                    className="z-50 rounded-xl border border-tinta/10 bg-white p-3 shadow-lg"
                >
                    {({ close }) => (
                        <DayPicker
                            mode="single"
                            locale={ptBR}
                            weekStartsOn={0}
                            showOutsideDays
                            selected={dataSelecionada}
                            onSelect={(data) => {
                                onChange(data ? format(data, FORMATO_ISO) : '');
                                close();
                            }}
                            classNames={{
                                today: `${defaultClassNames.today} text-ouro font-semibold`,
                                selected: `${defaultClassNames.selected} bg-ouro text-white rounded-md`,
                                root: `${defaultClassNames.root} font-sans`,
                                chevron: `${defaultClassNames.chevron} fill-tinta/60`,
                                day_button: `${defaultClassNames.day_button} rounded-md hover:bg-papel-sombra`,
                                outside: `${defaultClassNames.outside} text-tinta/25`,
                            }}
                        />
                    )}
                </PopoverPanel>
            </Transition>
        </Popover>
    );
}
