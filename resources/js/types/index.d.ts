export interface Usuario {
    id: string;
    nome: string;
    email: string;
    cor: string;
}

export interface Movimentacao {
    id: string;
    forma_pagamento_id: string;
    valor: number;
    data: string;
    competencia: string | null;
    is_saldo_inicial: boolean;
    forma_pagamento?: FormaPagamento;
}

export type TipoFormaPagamento = 'debito' | 'dinheiro' | 'pix' | 'credito' | 'vale' | 'beneficio';

export interface CartaoCredito {
    limite_total: number;
    limite_usado_abertura: number;
    dia_fechamento: number;
    dia_vencimento: number;
}

export interface FormaPagamento {
    id: string;
    conta_id: string;
    nome: string;
    tipo: TipoFormaPagamento;
    recebe_renda: boolean;
    saldo: number | null;
    cartao_credito: CartaoCredito | null;
    conta?: { id: string; nome: string; usuario?: { nome: string } };
}

export interface Conta {
    id: string;
    usuario_id: string;
    nome: string;
    logo_url: string;
    saldo_total: number;
    created_at: string;
    updated_at: string;
    formas_pagamento: FormaPagamento[];
}

export type TipoRecorrencia = 'unica' | 'mensal';

export interface ContaResumo {
    id: string;
    nome: string;
    logo_url: string;
}

export interface CategoriaRenda {
    id: string;
    nome: string;
    cor: string;
    icone: string;
}

export interface Renda {
    id: string;
    usuario_id: string;
    conta_id: string;
    categoria_renda_id: string;
    descricao: string;
    valor: number;
    tipo_recorrencia: TipoRecorrencia;
    data_recebimento: string | null;
    dia_recebimento: number | null;
    data_inicio: string | null;
    data_fim: string | null;
    conta: ContaResumo;
    categoria_renda: CategoriaRenda;
}

export interface OcorrenciaRenda {
    renda: Renda;
    competencia: string;
    recebida: boolean;
    movimentacao: Movimentacao | null;
    formas_pagamento_elegiveis: FormaPagamento[];
}

export type TipoLancamentoDespesa = 'unica' | 'mensal' | 'parcelada';
export type ContextoDespesa = 'individual' | 'conjunta';

export interface CategoriaDespesa {
    id: string;
    nome: string;
    cor: string;
    icone: string;
}

export interface Despesa {
    id: string;
    usuario_id: string;
    contexto: ContextoDespesa;
    forma_pagamento_id: string | null;
    categoria_despesa_id: string;
    descricao: string;
    valor: number;
    tipo_lancamento: TipoLancamentoDespesa;

    data_vencimento: string | null;

    dia_vencimento: number | null;
    data_inicio: string | null;
    data_fim: string | null;

    numero_parcelas: number | null;
    data_primeira_parcela: string | null;

    forma_pagamento?: FormaPagamento;
    categoria_despesa?: CategoriaDespesa;
}

export type StatusDespesa = 'vencida' | 'pendente' | 'paga';
export type StatusPagamentoFiltro = 'paga' | 'pendente';

export interface FiltrosDespesaValores {
    categoria_despesa_id?: string;
    tipo?: TipoLancamentoDespesa;
    forma_pagamento_id?: string;
    status?: StatusPagamentoFiltro;
    busca?: string;
}

export interface FormaPagamentoResumo {
    id: string;
    nome: string;
}

export interface OcorrenciaDespesa {
    despesa: Despesa;
    competencia: string;
    paga: boolean;
    status: StatusDespesa;
    numero_parcela: number | null;
    movimentacao: Movimentacao | null;
}

export type Toast = {
    type: 'success' | 'error';
    message: string;
};

export type ModoVisualizacao = 'individual' | 'casal';
export type StatusPeriodo = 'atual' | 'passado' | 'futuro';

export interface VariacaoDelta {
    tipo: 'percentual' | 'absoluto';
    valor: number;
}

export interface ResumoPeriodo {
    saldo: number;
    saldoDelta: VariacaoDelta | null;
    receita: number;
    receitaDelta: VariacaoDelta | null;
    despesa: number;
    despesaDelta: VariacaoDelta | null;
    resultado: number;
    resultadoDelta: VariacaoDelta | null;
    statusPeriodo: StatusPeriodo;
    temDespesaParcelada: boolean;
}

export interface PontoSerieSaldo {
    dia: number;
    valor: number;
    tipo: 'realizado' | 'projetado';
}

export interface EventoDia {
    descricao: string;
    valor: number;
}

export interface SerieSaldo {
    serie: PontoSerieSaldo[];
    eventosPorDia: Record<number, EventoDia[]>;
}

export interface CategoriaResumoItem {
    id: string | null;
    nome: string;
    cor: string;
    icone: string;
    valor: number;
    valorPago?: number;
    valorPendente?: number;
}

export interface FormaPagamentoResumoItem {
    nome: string;
    valor: number;
}

export interface IndividualXConjunta {
    individual: number;
    conjunta: number;
}

export type NivelPendencia = 'vencida' | 'vence_em_breve' | 'no_prazo';

export interface PendenciaItem {
    id: string;
    tipo: 'despesa' | 'renda';
    descricao: string;
    contexto: ContextoDespesa | null;
    tipoLancamento: TipoLancamentoDespesa | null;
    categoriaDespesaId: string | null;
    data: string;
    valor: number;
    dias: number;
    nivel: NivelPendencia;
}

export interface ContribuicaoPessoaItem {
    usuarioId: string;
    nome: string;
    cor: string;
    valor: number;
}

export interface ContribuicaoPorPessoa {
    receita: ContribuicaoPessoaItem[];
    despesa: ContribuicaoPessoaItem[];
}

export interface TendenciaMesItem {
    competencia: string;
    receita: number;
    despesa: number;
}

export interface UsuarioCasal {
    id: string;
    nome: string;
    cor: string;
}

export interface DashboardProps {
    modo: ModoVisualizacao;
    competencia: string;
    despesaRotulo: string;
    resumo: ResumoPeriodo;
    serieSaldo?: SerieSaldo;
    despesaPorCategoria: CategoriaResumoItem[];
    receitaPorCategoria: CategoriaResumoItem[];
    despesaPorFormaPagamento: FormaPagamentoResumoItem[];
    individualXConjunta: IndividualXConjunta | null;
    pendencias: PendenciaItem[];
    contribuicao?: ContribuicaoPorPessoa | null;
    tendencia6Meses?: TendenciaMesItem[];
    primeiroUso: boolean;
    usuariosCasal: UsuarioCasal[];
    categoriasDespesa: CategoriaDespesa[];
    formasPagamento: FormaPagamento[];
    contas: ContaResumo[];
    categoriasRenda: CategoriaRenda[];
    filtros: FiltrosDespesaValores;
    pessoaId: string | null;
    formasPagamentoFiltro: FormaPagamentoResumo[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        usuario: Usuario | null;
    };
};
