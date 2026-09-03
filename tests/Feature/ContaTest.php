<?php

use App\Domain\ValueObjects\Money;
use App\Enums\TipoFormaPagamento;
use App\Models\CartaoCredito;
use App\Models\Conta;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

function contaDe(Usuario $usuario, string $nome): Conta
{
    return Conta::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $usuario->id,
        'nome' => $nome,
    ]);
}

it('lista apenas as contas do usuário autenticado', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();

    contaDe($eu, 'Minha conta');
    contaDe($parceiro, 'Conta do parceiro');

    $this->actingAs($eu)
        ->get(route('contas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Contas/Index')
            ->has('contas', 1)
            ->where('contas.0.nome', 'Minha conta')
        );
});

it('ordena as contas por nome', function () {
    $eu = Usuario::factory()->create();

    contaDe($eu, 'Nubank');
    contaDe($eu, 'Bradesco');

    $this->actingAs($eu)
        ->get(route('contas.index'))
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina->where('contas.0.nome', 'Bradesco'));
});

it('calcula o saldo total da conta como soma das formas de pagamento não crédito', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDe($eu, 'Nubank');

    $debito = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Débito', 'tipo' => 'debito']);
    Movimentacao::create([
        'forma_pagamento_id' => $debito->id,
        'valor' => 10000,
        'data' => '2026-08-01',
        'is_saldo_inicial' => true,
    ]);
    Movimentacao::create(['forma_pagamento_id' => $debito->id, 'valor' => -3000, 'data' => '2026-08-05']);

    $credito = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Crédito', 'tipo' => 'credito']);
    CartaoCredito::create([
        'forma_pagamento_id' => $credito->id,
        'limite_total' => 500000,
        'dia_fechamento' => 10,
        'dia_vencimento' => 17,
    ]);

    $this->actingAs($eu)
        ->get(route('contas.index'))
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->where('contas.0.saldo_total', 7000)
        );
});

it('grava a conta no usuário autenticado', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('contas.store'), ['nome' => 'Itaú'])
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Conta criada com sucesso.']);

    $conta = Conta::withoutGlobalScope(DonoScope::class)->sole();

    expect($conta->nome)->toBe('Itaú')
        ->and($conta->usuario_id)->toBe($eu->id);
});

it('ignora usuario_id enviado no request', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('contas.store'), ['nome' => 'Itaú', 'usuario_id' => $parceiro->id]);

    expect(Conta::withoutGlobalScope(DonoScope::class)->sole()->usuario_id)->toBe($eu->id);
});

it('exige nome ao criar', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('contas.store'), ['nome' => ''])
        ->assertSessionHasErrors('nome');

    expect(Conta::withoutGlobalScope(DonoScope::class)->count())->toBe(0);
});

it('atualiza e exclui a própria conta', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDe($eu, 'Antigo');

    $this->actingAs($eu)
        ->put(route('contas.update', $conta), ['nome' => 'Novo'])
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Conta atualizada com sucesso.']);

    expect($conta->fresh()?->nome)->toBe('Novo');

    $this->actingAs($eu)
        ->delete(route('contas.destroy', $conta))
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Conta excluída com sucesso.']);

    expect(Conta::withoutGlobalScope(DonoScope::class)->count())->toBe(0);
});

it('soft-deleta a conta e cascateia para formas de pagamento e cartões', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDe($eu, 'Nubank');

    $forma = FormaPagamento::create([
        'conta_id' => $conta->id,
        'nome' => 'Pix',
        'tipo' => TipoFormaPagamento::Pix,
    ]);

    $formaCredito = FormaPagamento::create([
        'conta_id' => $conta->id,
        'nome' => 'Nubank Roxinho',
        'tipo' => TipoFormaPagamento::Credito,
    ]);

    $cartao = CartaoCredito::create([
        'forma_pagamento_id' => $formaCredito->id,
        'limite_total' => Money::fromCents(100000),
        'limite_usado_abertura' => Money::zero(),
        'dia_fechamento' => 20,
        'dia_vencimento' => 27,
    ]);

    $this->actingAs($eu)
        ->delete(route('contas.destroy', $conta))
        ->assertRedirect(route('contas.index'));

    expect(Conta::withoutGlobalScope(DonoScope::class)->withTrashed()->find($conta->id)?->deleted_at)->not->toBeNull()
        ->and(FormaPagamento::find($forma->id))->toBeNull()
        ->and(FormaPagamento::find($formaCredito->id))->toBeNull()
        ->and(FormaPagamento::withTrashed()->find($forma->id)?->deleted_at)->not->toBeNull()
        ->and(FormaPagamento::withTrashed()->find($formaCredito->id)?->deleted_at)->not->toBeNull()
        ->and(CartaoCredito::find($cartao->forma_pagamento_id))->not->toBeNull();
});

it('não cascateia soft delete ao forçar exclusão definitiva', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDe($eu, 'Itaú');

    $forma = FormaPagamento::create([
        'conta_id' => $conta->id,
        'nome' => 'Débito',
        'tipo' => TipoFormaPagamento::Debito,
    ]);

    $conta->forceDelete();

    expect(FormaPagamento::withTrashed()->find($forma->id))->toBeNull();
});

it('não alcança a conta do parceiro', function (string $metodo, string $rota) {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $conta = contaDe($parceiro, 'Conta do parceiro');

    $this->actingAs($eu)
        ->{$metodo}(route($rota, $conta), ['nome' => 'Invadida'])
        ->assertNotFound();

    $intacta = Conta::withoutGlobalScope(DonoScope::class)->find($conta->id);

    expect($intacta?->nome)->toBe('Conta do parceiro');
})->with([
    ['put', 'contas.update'],
    ['delete', 'contas.destroy'],
]);

it('nega a policy sobre a conta do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $conta = contaDe($parceiro, 'Conta do parceiro');

    expect($eu->can('view', $conta))->toBeFalse()
        ->and($eu->can('update', $conta))->toBeFalse()
        ->and($eu->can('delete', $conta))->toBeFalse();
});

it('exige autenticação nas rotas de conta', function () {
    $this->get(route('contas.index'))->assertRedirect(route('login'));
});

it('não devolve conta alguma sem usuário autenticado', function () {
    contaDe(Usuario::factory()->create(), 'Conta');

    Auth::logout();

    expect(Conta::query()->count())->toBe(0);
});
