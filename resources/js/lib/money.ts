const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const formatadorDePercentual = new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
});

export function formatarMoeda(centavos: number): string {
    return formatadorDeMoeda.format(centavos / 100);
}

export function formatarPercentual(valor: number): string {
    return formatadorDePercentual.format(Math.abs(valor)) + '%';
}
