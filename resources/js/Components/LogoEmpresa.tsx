type LogoDeEmpresa = {
    nome: string;
    url: string;
};

export default function LogoEmpresa({
    logos,
}: {
    logos: LogoDeEmpresa[];
}) {
    return (
        <div>
            <ul className="flex flex-wrap gap-4">
                {logos.map((logo) => (
                    <li key={logo.nome} className="flex flex-col items-center gap-2">
                        <img src={logo.url} alt={`Logo de ${logo.nome}`} width={64} height={64} />
                        <span className="text-sm">{logo.nome}</span>
                    </li>
                ))}
            </ul>
            <p className="mt-4 text-xs">
                Logos provided by <a href="https://logo.dev">Logo.dev</a>
            </p>
        </div>
    );
}
