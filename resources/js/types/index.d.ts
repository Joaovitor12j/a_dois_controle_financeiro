export interface Usuario {
    id: string;
    nome: string;
    email: string;
    cor: string;
}

export interface Conta {
    id: string;
    usuario_id: string;
    nome: string;
    created_at: string;
    updated_at: string;
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
