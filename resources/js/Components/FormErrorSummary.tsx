type ErrosFormulario = Partial<Record<string, string>>;

export default function FormErrorSummary({ errors }: { errors: ErrosFormulario }) {
    const mensagens = Object.values(errors).filter(
        (mensagem): mensagem is string => Boolean(mensagem),
    );

    if (mensagens.length === 0) {
        return null;
    }

    return (
        <div
            role="alert"
            className="mt-4 rounded-lg border border-vinho/30 bg-vinho/5 p-3"
        >
            <ul className="list-disc space-y-1 pl-5 text-sm font-medium text-vinho">
                {mensagens.map((mensagem, indice) => (
                    <li key={indice}>{mensagem}</li>
                ))}
            </ul>
        </div>
    );
}
