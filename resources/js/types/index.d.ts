export interface Usuario {
    id: string;
    nome: string;
    email: string;
    cor: string;
}

export interface Movimentacao {
    id: string;
    valor: number;
    data: string;
    is_saldo_inicial: boolean;
}

export type TipoFormaPagamento = 'debito' | 'dinheiro' | 'pix' | 'credito';

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
    saldo_inicial: Movimentacao | null;
    cartao_credito: CartaoCredito | null;
}

export interface Conta {
    id: string;
    usuario_id: string;
    nome: string;
    logo_url: string;
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

export type Toast = {
    type: 'success' | 'error';
    message: string;
};

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        usuario: Usuario | null;
    };
};
