import BlocoCondicional from '@/Components/BlocoCondicional';
import Checkbox from '@/Components/Checkbox';
import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { ModoEntradaParcelamento, useParcelamento } from '@/Hooks/useParcelamento';
import type {
    CategoriaDespesa,
    ContextoDespesa,
    Despesa,
    FormaPagamento,
    TipoLancamentoDespesa,
} from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useMemo, useState } from 'react';

const rotuloContexto: Record<ContextoDespesa, string> = {
    individual: 'Individual',
    conjunta: 'Conjunta',
};

const rotuloTipoLancamento: Record<TipoLancamentoDespesa, string> = {
    unica: 'Única',
    mensal: 'Mensal',
    parcelada: 'Parcelada',
};

const rotuloModoEntrada: Record<ModoEntradaParcelamento, string> = {
    total: 'Valor total',
    parcela: 'Valor da parcela',
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

function paraMesInput(data: string | null): string {
    return data ? data.slice(0, 7) : '';
}

function ToggleDuplo<T extends string>({
    valor,
    opcoes,
    onChange,
    disabled = false,
}: {
    valor: T;
    opcoes: { valor: T; rotulo: string }[];
    onChange: (valor: T) => void;
    disabled?: boolean;
}) {
    return (
        <div className="inline-flex rounded-lg border border-tinta/15 bg-white p-1">
            {opcoes.map((opcao) => (
                <button
                    key={opcao.valor}
                    type="button"
                    disabled={disabled}
                    onClick={() => onChange(opcao.valor)}
                    className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 ${
                        valor === opcao.valor
                            ? 'bg-verde text-white'
                            : 'text-tinta-claro hover:text-tinta'
                    }`}
                >
                    {opcao.rotulo}
                </button>
            ))}
        </div>
    );
}

export default function FormularioDespesa({
    despesa,
    categoriasDespesa,
    formasPagamento,
    aberto,
    aoFechar,
}: {
    despesa: Despesa | null;
    categoriasDespesa: CategoriaDespesa[];
    formasPagamento: FormaPagamento[];
    aberto: boolean;
    aoFechar: () => void;
}) {
    const [modoEntradaParcelamento, setModoEntradaParcelamento] =
        useState<ModoEntradaParcelamento>('parcela');

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
        contexto: despesa?.contexto ?? ('conjunta' as ContextoDespesa),
        categoria_despesa_id: despesa?.categoria_despesa_id ?? '',
        descricao: despesa?.descricao ?? '',
        tipo_lancamento:
            despesa?.tipo_lancamento ?? ('unica' as TipoLancamentoDespesa),
        valor: despesa ? paraReais(despesa.valor) : '',

        data_vencimento: paraDataInput(despesa?.data_vencimento ?? null),
        paga: despesa?.paga ?? false,
        data_pagamento: paraDataInput(despesa?.data_pagamento ?? null),
        forma_pagamento_id: despesa?.forma_pagamento_id ?? '',

        dia_vencimento:
            despesa?.dia_vencimento != null ? String(despesa.dia_vencimento) : '',
        data_inicio: paraMesInput(despesa?.data_inicio ?? null),
        data_fim: paraDataInput(despesa?.data_fim ?? null),

        numero_parcelas:
            despesa?.numero_parcelas != null ? String(despesa.numero_parcelas) : '',
        data_primeira_parcela: paraDataInput(despesa?.data_primeira_parcela ?? null),
    });

    const tipoEfetivo = despesa ? despesa.tipo_lancamento : data.tipo_lancamento;
    const ehUnica = tipoEfetivo === 'unica';
    const ehMensal = tipoEfetivo === 'mensal';
    const ehParcelada = tipoEfetivo === 'parcelada';

    const formasPagamentoCredito = useMemo(
        () => formasPagamento.filter((forma) => forma.tipo === 'credito'),
        [formasPagamento],
    );

    const valorDigitadoCentavos = paraCentavos(data.valor) ?? 0;
    const numeroParcelasNumero =
        data.numero_parcelas === '' ? 0 : Number(data.numero_parcelas);

    const { valorParcela, valorTotalExibido, diferencaArredondamento } =
        useParcelamento(
            valorDigitadoCentavos,
            numeroParcelasNumero,
            modoEntradaParcelamento,
        );

    transform((dados) => {
        const valorFinal = ehParcelada
            ? modoEntradaParcelamento === 'parcela'
                ? valorDigitadoCentavos
                : Math.round(valorDigitadoCentavos / (numeroParcelasNumero || 1))
            : valorDigitadoCentavos;

        return {
            contexto: dados.contexto,
            categoria_despesa_id: dados.categoria_despesa_id,
            descricao: dados.descricao,
            tipo_lancamento: despesa ? null : dados.tipo_lancamento,
            valor: valorFinal,

            // Única já paga na criação não pede vencimento na tela: data_vencimento
            // é preenchida com a própria data de pagamento (ver docs/domain/despesas.md).
            data_vencimento: ehUnica
                ? !despesa && dados.paga
                    ? dados.data_pagamento || null
                    : dados.data_vencimento || null
                : null,
            // "paga" tem NOT NULL + default(false) no banco: para mensal/parcelada e
            // em edição precisa ficar AUSENTE do payload (não null) pra cair no default.
            ...(!despesa && ehUnica ? { paga: dados.paga } : {}),
            ...(!despesa && ehUnica && dados.paga
                ? { data_pagamento: dados.data_pagamento || null }
                : {}),
            forma_pagamento_id: ehParcelada
                ? dados.forma_pagamento_id || null
                : ehUnica && !despesa
                    ? dados.forma_pagamento_id || null
                    : null,

            dia_vencimento:
                ehMensal && dados.dia_vencimento !== ''
                    ? Number(dados.dia_vencimento)
                    : null,
            data_inicio:
                ehMensal && dados.data_inicio !== ''
                    ? `${dados.data_inicio}-01`
                    : null,
            data_fim: ehMensal && dados.data_fim !== '' ? dados.data_fim : null,

            numero_parcelas:
                ehParcelada && dados.numero_parcelas !== ''
                    ? Number(dados.numero_parcelas)
                    : null,
            data_primeira_parcela: ehParcelada
                ? dados.data_primeira_parcela || null
                : null,
        };
    });

    const submit: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = { preserveScroll: true, onSuccess: aoFechar };

        if (despesa) {
            put(route('despesas.update', despesa.id), opcoes);
        } else {
            post(route('despesas.store'), opcoes);
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
                    {despesa ? 'Editar despesa' : 'Nova despesa'}
                </h2>

                <p className="mt-1.5 text-sm text-tinta-claro">
                    {despesa
                        ? 'Ajuste os dados desta saída financeira.'
                        : 'Uma saída financeira, vinculada a uma categoria de despesa.'}
                </p>

                <FormErrorSummary errors={errors} />

                <div className="mt-6 flex items-center justify-between gap-4">
                    <InputLabel value="Contexto" />

                    <ToggleDuplo
                        valor={data.contexto}
                        opcoes={Object.entries(rotuloContexto).map(
                            ([valor, rotulo]) => ({
                                valor: valor as ContextoDespesa,
                                rotulo,
                            }),
                        )}
                        onChange={(valor) => setData('contexto', valor)}
                    />
                </div>

                <InputError className="mt-2" message={errors.contexto} />

                <div className="mt-4">
                    <InputLabel htmlFor="descricao" value="Descrição" />

                    <TextInput
                        id="descricao"
                        className="mt-1.5 block w-full"
                        value={data.descricao}
                        onChange={(evento) =>
                            setData('descricao', evento.target.value)
                        }
                        placeholder="Aluguel, mercado, cartão de crédito…"
                        autoComplete="off"
                        maxLength={255}
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.descricao} />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="categoria_despesa_id"
                        value="Categoria"
                    />

                    <SelectInput
                        id="categoria_despesa_id"
                        className="mt-1.5 block w-full"
                        value={data.categoria_despesa_id}
                        onChange={(evento) =>
                            setData('categoria_despesa_id', evento.target.value)
                        }
                    >
                        <option value="" disabled>
                            Selecione…
                        </option>
                        {categoriasDespesa.map((categoria) => (
                            <option key={categoria.id} value={categoria.id}>
                                {categoria.nome}
                            </option>
                        ))}
                    </SelectInput>

                    <InputError
                        className="mt-2"
                        message={errors.categoria_despesa_id}
                    />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="tipo_lancamento" value="Tipo de lançamento" />

                    {despesa ? (
                        <p className="mt-1.5 text-sm font-medium text-tinta">
                            {rotuloTipoLancamento[despesa.tipo_lancamento]}
                        </p>
                    ) : (
                        <>
                            <SelectInput
                                id="tipo_lancamento"
                                className="mt-1.5 block w-full"
                                value={data.tipo_lancamento}
                                onChange={(evento) =>
                                    setData(
                                        'tipo_lancamento',
                                        evento.target.value as TipoLancamentoDespesa,
                                    )
                                }
                            >
                                {Object.entries(rotuloTipoLancamento).map(
                                    ([valor, rotulo]) => (
                                        <option key={valor} value={valor}>
                                            {rotulo}
                                        </option>
                                    ),
                                )}
                            </SelectInput>

                            <InputError
                                className="mt-2"
                                message={errors.tipo_lancamento}
                            />
                        </>
                    )}
                </div>

                <BlocoCondicional aberto={ehUnica}>
                    <div className="mt-4">
                        <InputLabel htmlFor="valor_unica" value="Valor" />

                        <TextInput
                            id="valor_unica"
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

                    {!despesa && (
                        <div className="mt-4">
                            <label className="flex items-center gap-2">
                                <Checkbox
                                    checked={data.paga}
                                    onChange={(evento) => {
                                        const marcado = evento.target.checked;

                                        setData((atual) => ({
                                            ...atual,
                                            paga: marcado,
                                            data_pagamento:
                                                marcado && atual.data_pagamento === ''
                                                    ? atual.data_vencimento
                                                    : atual.data_pagamento,
                                        }));
                                    }}
                                />

                                <span className="text-sm font-medium text-tinta">
                                    Já paga
                                </span>
                            </label>

                            <InputError className="mt-2" message={errors.paga} />
                        </div>
                    )}

                    {despesa && (
                        <div className="mt-4">
                            <span
                                className={`inline-block rounded-full px-3 py-1 text-xs font-semibold ${
                                    despesa.paga
                                        ? 'bg-verde/10 text-verde'
                                        : 'bg-tinta/10 text-tinta-claro'
                                }`}
                            >
                                {despesa.paga ? 'Paga' : 'Pendente'}
                            </span>
                        </div>
                    )}

                    <BlocoCondicional aberto={!despesa && data.paga}>
                        <div className="mt-4">
                            <InputLabel
                                htmlFor="data_pagamento"
                                value="Data de pagamento"
                            />

                            <TextInput
                                id="data_pagamento"
                                type="date"
                                className="mt-1.5 block w-full"
                                value={data.data_pagamento}
                                onChange={(evento) =>
                                    setData('data_pagamento', evento.target.value)
                                }
                            />

                            <InputError
                                className="mt-2"
                                message={errors.data_pagamento}
                            />
                        </div>

                        <div className="mt-4">
                            <InputLabel
                                htmlFor="forma_pagamento_id"
                                value="Forma de pagamento"
                            />

                            <SelectInput
                                id="forma_pagamento_id"
                                className="mt-1.5 block w-full"
                                value={data.forma_pagamento_id}
                                onChange={(evento) =>
                                    setData(
                                        'forma_pagamento_id',
                                        evento.target.value,
                                    )
                                }
                            >
                                <option value="" disabled>
                                    Selecione…
                                </option>
                                {formasPagamento.map((forma) => (
                                    <option key={forma.id} value={forma.id}>
                                        {forma.nome}
                                    </option>
                                ))}
                            </SelectInput>

                            <InputError
                                className="mt-2"
                                message={errors.forma_pagamento_id}
                            />
                        </div>
                    </BlocoCondicional>

                    <BlocoCondicional aberto={despesa ? !despesa.paga : !data.paga}>
                        <div className="mt-4">
                            <InputLabel
                                htmlFor="data_vencimento"
                                value="Data de vencimento"
                            />

                            <TextInput
                                id="data_vencimento"
                                type="date"
                                className="mt-1.5 block w-full"
                                value={data.data_vencimento}
                                onChange={(evento) =>
                                    setData('data_vencimento', evento.target.value)
                                }
                            />

                            <InputError
                                className="mt-2"
                                message={errors.data_vencimento}
                            />
                        </div>
                    </BlocoCondicional>
                </BlocoCondicional>

                <BlocoCondicional aberto={ehMensal}>
                    <div className="mt-4">
                        <InputLabel htmlFor="valor_mensal" value="Valor" />

                        <TextInput
                            id="valor_mensal"
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

                    <div className="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                htmlFor="dia_vencimento"
                                value="Dia de vencimento"
                            />

                            <TextInput
                                id="dia_vencimento"
                                type="number"
                                className="mt-1.5 block w-full"
                                min={1}
                                max={31}
                                value={data.dia_vencimento}
                                onChange={(evento) =>
                                    setData('dia_vencimento', evento.target.value)
                                }
                            />

                            <InputError
                                className="mt-2"
                                message={errors.dia_vencimento}
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="data_inicio"
                                value="Mês de início"
                            />

                            <TextInput
                                id="data_inicio"
                                type="month"
                                className="mt-1.5 block w-full"
                                value={data.data_inicio}
                                onChange={(evento) =>
                                    setData('data_inicio', evento.target.value)
                                }
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

                        <TextInput
                            id="data_fim"
                            type="date"
                            className="mt-1.5 block w-full"
                            value={data.data_fim}
                            onChange={(evento) =>
                                setData('data_fim', evento.target.value)
                            }
                        />

                        <InputError className="mt-2" message={errors.data_fim} />
                    </div>
                </BlocoCondicional>

                <BlocoCondicional aberto={ehParcelada}>
                    <div className="mt-4">
                        <InputLabel
                            htmlFor="forma_pagamento_id_parcelada"
                            value="Forma de pagamento (crédito)"
                        />

                        <SelectInput
                            id="forma_pagamento_id_parcelada"
                            className="mt-1.5 block w-full"
                            value={data.forma_pagamento_id}
                            onChange={(evento) =>
                                setData('forma_pagamento_id', evento.target.value)
                            }
                        >
                            <option value="" disabled>
                                Selecione…
                            </option>
                            {formasPagamentoCredito.map((forma) => (
                                <option key={forma.id} value={forma.id}>
                                    {forma.nome}
                                </option>
                            ))}
                        </SelectInput>

                        <InputError
                            className="mt-2"
                            message={errors.forma_pagamento_id}
                        />
                    </div>

                    <div className="mt-4 flex items-center justify-between gap-4">
                        <InputLabel value="Como informar o valor" />

                        <ToggleDuplo
                            valor={modoEntradaParcelamento}
                            opcoes={Object.entries(rotuloModoEntrada).map(
                                ([valor, rotulo]) => ({
                                    valor: valor as ModoEntradaParcelamento,
                                    rotulo,
                                }),
                            )}
                            onChange={setModoEntradaParcelamento}
                        />
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                htmlFor="numero_parcelas"
                                value="Número de parcelas"
                            />

                            <TextInput
                                id="numero_parcelas"
                                type="number"
                                className="mt-1.5 block w-full"
                                min={1}
                                value={data.numero_parcelas}
                                onChange={(evento) =>
                                    setData('numero_parcelas', evento.target.value)
                                }
                            />

                            <InputError
                                className="mt-2"
                                message={errors.numero_parcelas}
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="valor_parcelada"
                                value={rotuloModoEntrada[modoEntradaParcelamento]}
                            />

                            <TextInput
                                id="valor_parcelada"
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
                    </div>

                    <p className="mt-2 text-sm text-tinta-claro">
                        Parcela de R$ {paraReais(valorParcela)} × {numeroParcelasNumero || 0}{' '}
                        = R$ {paraReais(valorTotalExibido)}
                        {modoEntradaParcelamento === 'total' &&
                            diferencaArredondamento !== 0 && (
                                <>
                                    {' '}
                                    (diferença de R$ {paraReais(
                                        Math.abs(diferencaArredondamento),
                                    )}{' '}
                                    em relação ao total informado, por
                                    arredondamento)
                                </>
                            )}
                    </p>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="data_primeira_parcela"
                            value="Data da primeira parcela"
                        />

                        <TextInput
                            id="data_primeira_parcela"
                            type="date"
                            className="mt-1.5 block w-full"
                            value={data.data_primeira_parcela}
                            onChange={(evento) =>
                                setData(
                                    'data_primeira_parcela',
                                    evento.target.value,
                                )
                            }
                        />

                        <InputError
                            className="mt-2"
                            message={errors.data_primeira_parcela}
                        />
                    </div>
                </BlocoCondicional>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        {despesa ? 'Salvar' : 'Criar despesa'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
