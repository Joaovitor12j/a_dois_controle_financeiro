import DateInput from '@/Components/DateInput';
import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import type {
    CategoriaRenda,
    ContaResumo,
    Renda,
    TipoRecorrencia,
} from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const rotuloTipoRecorrencia: Record<TipoRecorrencia, string> = {
    unica: 'Única',
    mensal: 'Mensal',
};

function paraCentavos(valorEmReais: string): number | null {
    const normalizado = valorEmReais.trim().replace(',', '.');

    if (normalizado === '') {
        return null;
    }

    const numero = Number(normalizado);

    return Number.isFinite(numero) ? Math.round(numero * 100) : null;
}

function paraReais(valorEmCentavos: number): string {
    return (valorEmCentavos / 100).toFixed(2).replace('.', ',');
}

function paraDataInput(data: string | null): string {
    return data ? data.slice(0, 10) : '';
}

export default function FormularioRenda({
    renda,
    contas,
    categoriasRenda,
    aberto,
    aoFechar,
}: {
    renda: Renda | null;
    contas: ContaResumo[];
    categoriasRenda: CategoriaRenda[];
    aberto: boolean;
    aoFechar: () => void;
}) {
    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        clearErrors,
        transform,
    } = useForm({
        conta_id: renda?.conta_id ?? (contas[0]?.id ?? ''),
        categoria_renda_id:
            renda?.categoria_renda_id ?? (categoriasRenda[0]?.id ?? ''),
        descricao: renda?.descricao ?? '',
        valor: renda ? paraReais(renda.valor) : '',
        tipo_recorrencia: renda?.tipo_recorrencia ?? ('unica' as TipoRecorrencia),
        data_recebimento: paraDataInput(renda?.data_recebimento ?? null),
        dia_recebimento:
            renda?.dia_recebimento != null ? String(renda.dia_recebimento) : '',
        data_inicio: paraDataInput(renda?.data_inicio ?? null),
        data_fim: paraDataInput(renda?.data_fim ?? null),
    });

    const ehUnica = data.tipo_recorrencia === 'unica';

    transform((dados) => ({
        ...dados,
        valor: paraCentavos(dados.valor),
        data_recebimento:
            dados.tipo_recorrencia === 'unica'
                ? dados.data_recebimento || null
                : null,
        dia_recebimento:
            dados.tipo_recorrencia === 'mensal' && dados.dia_recebimento !== ''
                ? Number(dados.dia_recebimento)
                : null,
        data_inicio:
            dados.tipo_recorrencia === 'mensal' ? dados.data_inicio || null : null,
        data_fim:
            dados.tipo_recorrencia === 'mensal' && dados.data_fim !== ''
                ? dados.data_fim
                : null,
    }));

    const submit: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = { preserveScroll: true, onSuccess: aoFechar };

        if (renda) {
            put(route('rendas.update', renda.id), opcoes);
        } else {
            post(route('rendas.store'), opcoes);
        }
    };

    const fechar = () => {
        clearErrors();
        aoFechar();
    };

    return (
        <Modal show={aberto} onClose={fechar} maxWidth="md">
            <form onSubmit={submit} className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    {renda ? 'Editar renda' : 'Nova renda'}
                </h2>

                <p className="mt-1.5 text-sm text-tinta-claro">
                    {renda
                        ? 'Ajuste os dados desta entrada financeira.'
                        : 'Uma entrada financeira, vinculada a uma conta e a uma categoria.'}
                </p>

                <FormErrorSummary errors={errors} />

                <div className="mt-6">
                    <InputLabel htmlFor="descricao" value="Descrição" />

                    <TextInput
                        id="descricao"
                        className="mt-1.5 block w-full"
                        value={data.descricao}
                        onChange={(evento) =>
                            setData('descricao', evento.target.value)
                        }
                        placeholder="Salário, freela, aluguel recebido…"
                        autoComplete="off"
                        maxLength={255}
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.descricao} />
                </div>

                <div className="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="conta_id" value="Conta" />

                        <SelectInput
                            id="conta_id"
                            className="mt-1.5 block w-full"
                            value={data.conta_id}
                            onChange={(evento) =>
                                setData('conta_id', evento.target.value)
                            }
                        >
                            <option value="" disabled>
                                Selecione…
                            </option>
                            {contas.map((conta) => (
                                <option key={conta.id} value={conta.id}>
                                    {conta.nome}
                                </option>
                            ))}
                        </SelectInput>

                        <InputError className="mt-2" message={errors.conta_id} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="categoria_renda_id"
                            value="Categoria"
                        />

                        <SelectInput
                            id="categoria_renda_id"
                            className="mt-1.5 block w-full"
                            value={data.categoria_renda_id}
                            onChange={(evento) =>
                                setData(
                                    'categoria_renda_id',
                                    evento.target.value,
                                )
                            }
                        >
                            <option value="" disabled>
                                Selecione…
                            </option>
                            {categoriasRenda.map((categoria) => (
                                <option key={categoria.id} value={categoria.id}>
                                    {categoria.nome}
                                </option>
                            ))}
                        </SelectInput>

                        <InputError
                            className="mt-2"
                            message={errors.categoria_renda_id}
                        />
                    </div>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="valor" value="Valor" />

                        <TextInput
                            id="valor"
                            className="mt-1.5 block w-full"
                            inputMode="decimal"
                            value={data.valor}
                            onChange={(evento) =>
                                setData('valor', evento.target.value)
                            }
                            placeholder="0,00"
                        />

                        <InputError className="mt-2" message={errors.valor} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="tipo_recorrencia"
                            value="Recorrência"
                        />

                        <SelectInput
                            id="tipo_recorrencia"
                            className="mt-1.5 block w-full"
                            value={data.tipo_recorrencia}
                            onChange={(evento) =>
                                setData(
                                    'tipo_recorrencia',
                                    evento.target.value as TipoRecorrencia,
                                )
                            }
                        >
                            {Object.entries(rotuloTipoRecorrencia).map(
                                ([valor, rotulo]) => (
                                    <option key={valor} value={valor}>
                                        {rotulo}
                                    </option>
                                ),
                            )}
                        </SelectInput>

                        <InputError
                            className="mt-2"
                            message={errors.tipo_recorrencia}
                        />
                    </div>
                </div>

                {ehUnica ? (
                    <div className="mt-4">
                        <InputLabel
                            htmlFor="data_recebimento"
                            value="Data de recebimento"
                        />

                        <DateInput
                            id="data_recebimento"
                            className="mt-1.5 block w-full"
                            value={data.data_recebimento}
                            onChange={(valor) =>
                                setData('data_recebimento', valor)
                            }
                            hasError={Boolean(errors.data_recebimento)}
                        />

                        <InputError
                            className="mt-2"
                            message={errors.data_recebimento}
                        />
                    </div>
                ) : (
                    <>
                        <div className="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel
                                    htmlFor="dia_recebimento"
                                    value="Dia de recebimento"
                                />

                                <TextInput
                                    id="dia_recebimento"
                                    type="number"
                                    className="mt-1.5 block w-full"
                                    min={1}
                                    max={31}
                                    value={data.dia_recebimento}
                                    onChange={(evento) =>
                                        setData(
                                            'dia_recebimento',
                                            evento.target.value,
                                        )
                                    }
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.dia_recebimento}
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="data_inicio"
                                    value="Data de início"
                                />

                                <DateInput
                                    id="data_inicio"
                                    className="mt-1.5 block w-full"
                                    value={data.data_inicio}
                                    onChange={(valor) =>
                                        setData('data_inicio', valor)
                                    }
                                    hasError={Boolean(errors.data_inicio)}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.data_inicio}
                                />
                            </div>
                        </div>

                        <div className="mt-4">
                            <InputLabel
                                htmlFor="data_fim"
                                value="Data de fim (opcional)"
                            />

                            <DateInput
                                id="data_fim"
                                className="mt-1.5 block w-full"
                                value={data.data_fim}
                                onChange={(valor) => setData('data_fim', valor)}
                                hasError={Boolean(errors.data_fim)}
                            />

                            <InputError className="mt-2" message={errors.data_fim} />
                        </div>
                    </>
                )}

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        {renda ? 'Salvar' : 'Criar renda'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
