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

export type TipoFormaPagamento = 'debito' | 'dinheiro' | 'pix';

export interface FormaPagamento {
    id: string;
    conta_id: string;
    nome: string;
    tipo: TipoFormaPagamento;
    saldo_inicial: Movimentacao | null;
}

export interface CartaoCredito {
    id: string;
    conta_id: string;
    nome: string;
    limite_total: number;
    limite_usado_abertura: number;
    dia_fechamento: number;
    dia_vencimento: number;
}

export interface Conta {
    id: string;
    usuario_id: string;
    nome: string;
    created_at: string;
    updated_at: string;
    formas_pagamento: FormaPagamento[];
    cartoes_credito: CartaoCredito[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        usuario: Usuario | null;
    };
    flash: {
        status: string | null;
    };
};
