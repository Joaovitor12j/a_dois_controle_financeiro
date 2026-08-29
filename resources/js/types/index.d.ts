export interface Usuario {
    id: string;
    nome: string;
    email: string;
    cor: string;
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
