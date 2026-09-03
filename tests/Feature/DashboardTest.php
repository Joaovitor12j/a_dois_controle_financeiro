<?php

use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Enums\ContextoDespesa;
use App\Models\CategoriaDespesa;
use App\Models\CategoriaRenda;
use App\Models\Conta;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Renda;
use App\Models\Scopes\DespesaScope;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use App\Services\Financeiro\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

function casalDeTeste(): object
{
    /** @var array<int, array{email: string}> $iniciais */
    $iniciais = config('usuarios.iniciais');

    $joao = Usuario::factory()->create(['nome' => 'João', 'cor' => '#2F6F5E', 'email' => $iniciais[0]['email']]);
    $elisa = Usuario::factory()->create(['nome' => 'Elisa', 'cor' => '#7B3F55', 'email' => $iniciais[1]['email']]);

    $contaJoao = Conta::withoutGlobalScope(DonoScope::class)->create(['usuario_id' => $joao->id, 'nome' => 'Conta João']);
    $contaElisa = Conta::withoutGlobalScope(DonoScope::class)->create(['usuario_id' => $elisa->id, 'nome' => 'Conta Elisa']);

    $formaJoao = FormaPagamento::create(['conta_id' => $contaJoao->id, 'nome' => 'Débito João', 'tipo' => 'debito']);
    $formaElisa = FormaPagamento::create(['conta_id' => $contaElisa->id, 'nome' => 'Débito Elisa', 'tipo' => 'debito']);

    $categoriaDespesa = CategoriaDespesa::create(['nome' => 'Moradia', 'cor' => '#7B3F55', 'icone' => 'home']);
    $categoriaRenda = CategoriaRenda::create(['nome' => 'Salário', 'cor' => '#2F6F5E', 'icone' => 'wallet']);

    Auth::login($joao);

    return (object) compact('joao', 'elisa', 'contaJoao', 'contaElisa', 'formaJoao', 'formaElisa', 'categoriaDespesa', 'categoriaRenda');
}

beforeEach(function () {
    Carbon::setTestNow('2026-09-15');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('modo individual soma só a renda e a despesa do usuário autenticado', function () {
    $c = casalDeTeste();

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário João',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-05',
    ]);

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->elisa->id,
        'conta_id' => $c->contaElisa->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário Elisa',
        'valor' => Money::fromCents(300000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-05',
    ]);

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $c->joao->id,
        'contexto' => ContextoDespesa::Individual,
        'categoria_despesa_id' => $c->categoriaDespesa->id,
        'descricao' => 'Academia',
        'valor' => Money::fromCents(10000),
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-09-20',
    ]);

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $c->elisa->id,
        'contexto' => ContextoDespesa::Individual,
        'categoria_despesa_id' => $c->categoriaDespesa->id,
        'descricao' => 'Curso',
        'valor' => Money::fromCents(20000),
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-09-20',
    ]);

    $resumo = app(DashboardService::class)->obterResumo('individual', Competencia::deString('2026-09'));

    expect($resumo['resumo']['receita'])->toBe(500000)
        ->and($resumo['resumo']['despesa'])->toBe(10000)
        ->and($resumo['contribuicao'])->toBeNull();
});

it('modo casal soma a renda dos dois usuários e só a despesa conjunta', function () {
    $c = casalDeTeste();

    $rendaJoao = Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário João',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-05',
    ]);

    $rendaElisa = Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->elisa->id,
        'conta_id' => $c->contaElisa->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário Elisa',
        'valor' => Money::fromCents(300000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-05',
    ]);

    Movimentacao::create([
        'forma_pagamento_id' => $c->formaJoao->id,
        'valor' => Money::fromCents(500000),
        'data' => '2026-09-05',
        'renda_id' => $rendaJoao->id,
        'competencia' => '2026-09-01',
    ]);

    Movimentacao::create([
        'forma_pagamento_id' => $c->formaElisa->id,
        'valor' => Money::fromCents(300000),
        'data' => '2026-09-05',
        'renda_id' => $rendaElisa->id,
        'competencia' => '2026-09-01',
    ]);

    $despesaIndividualJoao = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $c->joao->id,
        'contexto' => ContextoDespesa::Individual,
        'categoria_despesa_id' => $c->categoriaDespesa->id,
        'descricao' => 'Academia',
        'valor' => Money::fromCents(10000),
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-09-20',
    ]);

    $despesaConjunta = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $c->joao->id,
        'contexto' => ContextoDespesa::Conjunta,
        'categoria_despesa_id' => $c->categoriaDespesa->id,
        'descricao' => 'Aluguel',
        'valor' => Money::fromCents(240000),
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-09-05',
    ]);

    Movimentacao::create([
        'forma_pagamento_id' => $c->formaElisa->id,
        'valor' => Money::fromCents(-240000),
        'data' => '2026-09-05',
        'despesa_id' => $despesaConjunta->id,
        'competencia' => '2026-09-01',
    ]);

    $resumo = app(DashboardService::class)->obterResumo('casal', Competencia::deString('2026-09'));

    /** @var array<int, array{usuarioId: string, valor: int}> $despesaPorPessoa */
    $despesaPorPessoa = $resumo['contribuicao']['despesa'];

    /** @var array<int, array{usuarioId: string, valor: int}> $receitaPorPessoa */
    $receitaPorPessoa = $resumo['contribuicao']['receita'];

    expect($resumo['resumo']['receita'])->toBe(800000)
        ->and($resumo['resumo']['despesa'])->toBe(240000)
        ->and($resumo['despesaRotulo'])->toBe('Despesa conjunta')
        ->and($resumo['contribuicao']['receita'])->toHaveCount(2)
        ->and(collect($receitaPorPessoa)->firstWhere('usuarioId', $c->joao->id)['valor'])->toBe(500000)
        ->and(collect($receitaPorPessoa)->firstWhere('usuarioId', $c->elisa->id)['valor'])->toBe(300000)
        ->and(collect($despesaPorPessoa)->firstWhere('usuarioId', $c->elisa->id)['valor'])->toBe(240000)
        ->and(collect($despesaPorPessoa)->firstWhere('usuarioId', $c->joao->id)['valor'])->toBe(0);

    expect($resumo['pendencias'])->toHaveCount(0);
});

it('lista renda não recebida em pendências e alertas, junto com despesa não paga', function () {
    $c = casalDeTeste();

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Freela',
        'valor' => Money::fromCents(150000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-16',
    ]);

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $c->joao->id,
        'contexto' => ContextoDespesa::Individual,
        'categoria_despesa_id' => $c->categoriaDespesa->id,
        'descricao' => 'Academia',
        'valor' => Money::fromCents(10000),
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-09-18',
    ]);

    $resumo = app(DashboardService::class)->obterResumo('individual', Competencia::deString('2026-09'));

    /** @var array<int, array{id: string, tipo: string, descricao: string}> $pendencias */
    $pendencias = $resumo['pendencias'];

    /** @var array<int, array{titulo: string}> $alertas */
    $alertas = $resumo['alertas'];

    expect($pendencias)->toHaveCount(2)
        ->and(collect($pendencias)->pluck('tipo')->all())->toBe(['renda', 'despesa'])
        ->and($alertas)->toHaveCount(2)
        ->and(collect($alertas)->pluck('titulo')->first())->toContain('Freela');
});

it('só conta renda como realizada na série do saldo quando existe movimentação de recebimento', function () {
    $c = casalDeTeste();

    $rendaRecebida = Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário recebido',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-05',
    ]);

    Movimentacao::create([
        'forma_pagamento_id' => $c->formaJoao->id,
        'valor' => Money::fromCents(500000),
        'data' => '2026-09-05',
        'renda_id' => $rendaRecebida->id,
        'competencia' => '2026-09-01',
    ]);

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Freela ainda não recebido',
        'valor' => Money::fromCents(50000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-10',
    ]);

    $resumo = app(DashboardService::class)->obterResumo('individual', Competencia::deString('2026-09'));

    expect($resumo['resumo']['saldo'])->toBe(500000)
        ->and($resumo['resumo']['receita'])->toBe(550000);
});

it('resolve contribuição por pessoa mesmo quando a forma de pagamento usada no pagamento foi excluída depois', function () {
    $c = casalDeTeste();

    $despesaConjunta = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $c->joao->id,
        'contexto' => ContextoDespesa::Conjunta,
        'categoria_despesa_id' => $c->categoriaDespesa->id,
        'descricao' => 'Aluguel',
        'valor' => Money::fromCents(240000),
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-09-05',
    ]);

    Movimentacao::create([
        'forma_pagamento_id' => $c->formaElisa->id,
        'valor' => Money::fromCents(-240000),
        'data' => '2026-09-05',
        'despesa_id' => $despesaConjunta->id,
        'competencia' => '2026-09-01',
    ]);

    $c->formaElisa->delete();

    $resumo = app(DashboardService::class)->obterResumo('casal', Competencia::deString('2026-09'));

    /** @var array<int, array{usuarioId: string, valor: int}> $despesaPorPessoa */
    $despesaPorPessoa = $resumo['contribuicao']['despesa'];

    expect(collect($despesaPorPessoa)->firstWhere('usuarioId', $c->elisa->id)['valor'])->toBe(240000);
});

it('calcula variação percentual em relação ao mês anterior', function () {
    $c = casalDeTeste();

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário João agosto',
        'valor' => Money::fromCents(400000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-08-05',
    ]);

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário João setembro',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-09-05',
    ]);

    $resumo = app(DashboardService::class)->obterResumo('individual', Competencia::deString('2026-09'));

    expect($resumo['resumo']['receitaDeltaPct'])->toBe(25.0);
});

it('não realiza nada num período futuro e não quebra a série do saldo', function () {
    $c = casalDeTeste();

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $c->joao->id,
        'conta_id' => $c->contaJoao->id,
        'categoria_renda_id' => $c->categoriaRenda->id,
        'descricao' => 'Salário João dezembro',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-12-05',
    ]);

    $resumo = app(DashboardService::class)->obterResumo('individual', Competencia::deString('2026-12'));

    /** @var array<int, array{tipo: string}> $serieSaldo */
    $serieSaldo = $resumo['serieSaldo'];

    expect($resumo['resumo']['saldo'])->toBe(0)
        ->and($resumo['resumo']['receita'])->toBe(500000)
        ->and(collect($serieSaldo)->pluck('tipo')->unique()->values()->all())->toBe(['realizado', 'projetado']);
});
