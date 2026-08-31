<?php

use App\Models\CartaoCredito;
use App\Models\Conta;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;

function contaComDonoDe(Usuario $usuario, string $nome): Conta
{
    return Conta::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $usuario->id,
        'nome' => $nome,
    ]);
}

it('cria forma de pagamento sem saldo inicial', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Pix Nubank',
            'tipo' => 'pix',
        ])
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Forma de pagamento criada com sucesso.']);

    $forma = FormaPagamento::sole();

    expect($forma->nome)->toBe('Pix Nubank')
        ->and($forma->tipo->value)->toBe('pix')
        ->and($forma->saldoInicial)->toBeNull();
});

it('cria forma de pagamento com saldo inicial', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Carteira',
            'tipo' => 'dinheiro',
            'saldo_inicial' => 5000,
            'data_saldo_inicial' => '2026-08-01',
        ])
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Forma de pagamento criada com sucesso.']);

    $forma = FormaPagamento::sole();
    $movimentacao = Movimentacao::sole();

    expect($movimentacao->forma_pagamento_id)->toBe($forma->id)
        ->and($movimentacao->is_saldo_inicial)->toBeTrue();
});

it('exige data quando informa saldo inicial', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Carteira',
            'tipo' => 'dinheiro',
            'saldo_inicial' => 5000,
        ])
        ->assertSessionHasErrors('data_saldo_inicial');

    expect(FormaPagamento::count())->toBe(0);
});

it('atualiza a própria forma de pagamento', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');
    $forma = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Débito', 'tipo' => 'debito']);

    $this->actingAs($eu)
        ->put(route('formas-pagamento.update', $forma), ['nome' => 'Débito Nubank'])
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Forma de pagamento atualizada com sucesso.']);

    expect($forma->fresh()?->nome)->toBe('Débito Nubank');
});

it('não atualiza forma de pagamento do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $conta = contaComDonoDe($parceiro, 'Conta do parceiro');
    $forma = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Débito', 'tipo' => 'debito']);

    $this->actingAs($eu)
        ->put(route('formas-pagamento.update', $forma), ['nome' => 'Invadida', 'tipo' => 'debito'])
        ->assertForbidden();

    expect($forma->fresh()?->nome)->toBe('Débito');
});

it('cria forma de pagamento do tipo crédito com a extensão', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Nubank Roxinho',
            'tipo' => 'credito',
            'limite_total' => 500000,
            'limite_usado_abertura' => 12345,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
        ])
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Forma de pagamento criada com sucesso.']);

    $forma = FormaPagamento::sole();
    $cartao = CartaoCredito::sole();

    expect($forma->tipo->value)->toBe('credito')
        ->and($cartao->forma_pagamento_id)->toBe($forma->id)
        ->and($cartao->limite_total->cents)->toBe(500000)
        ->and($cartao->limite_usado_abertura->cents)->toBe(12345)
        ->and($cartao->dia_fechamento)->toBe(10)
        ->and($cartao->dia_vencimento)->toBe(17);
});

it('rejeita campos de crédito em forma de pagamento que não é crédito', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Pix',
            'tipo' => 'pix',
            'limite_total' => 500000,
        ])
        ->assertSessionHasErrors('limite_total');

    expect(FormaPagamento::count())->toBe(0);
});

it('exige os campos de crédito ao criar forma de pagamento do tipo crédito', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Cartão',
            'tipo' => 'credito',
        ])
        ->assertSessionHasErrors(['limite_total', 'dia_fechamento', 'dia_vencimento']);

    expect(FormaPagamento::count())->toBe(0);
});

it('rejeita saldo inicial em forma de pagamento do tipo crédito', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');

    $this->actingAs($eu)
        ->post(route('formas-pagamento.store'), [
            'conta_id' => $conta->id,
            'nome' => 'Cartão',
            'tipo' => 'credito',
            'limite_total' => 500000,
            'dia_fechamento' => 10,
            'dia_vencimento' => 17,
            'saldo_inicial' => 5000,
            'data_saldo_inicial' => '2026-08-01',
        ])
        ->assertSessionHasErrors('saldo_inicial');

    expect(FormaPagamento::count())->toBe(0);
});

it('rejeita tipo no payload de atualização', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');
    $forma = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Débito', 'tipo' => 'debito']);

    $this->actingAs($eu)
        ->put(route('formas-pagamento.update', $forma), ['nome' => 'Débito Nubank', 'tipo' => 'pix'])
        ->assertSessionHasErrors('tipo');

    expect($forma->fresh()?->tipo->value)->toBe('debito');
});

it('atualiza os campos de crédito da própria forma de pagamento', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');
    $forma = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Cartão', 'tipo' => 'credito']);
    CartaoCredito::create([
        'forma_pagamento_id' => $forma->id,
        'limite_total' => 500000,
        'limite_usado_abertura' => 0,
        'dia_fechamento' => 10,
        'dia_vencimento' => 17,
    ]);

    $this->actingAs($eu)
        ->put(route('formas-pagamento.update', $forma), [
            'nome' => 'Nubank Roxinho',
            'limite_total' => 600000,
            'dia_fechamento' => 5,
            'dia_vencimento' => 12,
        ])
        ->assertRedirect(route('contas.index'));

    $cartao = $forma->cartaoCredito->fresh();

    expect($forma->fresh()?->nome)->toBe('Nubank Roxinho')
        ->and($cartao->limite_total->cents)->toBe(600000)
        ->and($cartao->dia_fechamento)->toBe(5)
        ->and($cartao->dia_vencimento)->toBe(12)
        ->and($cartao->limite_usado_abertura->cents)->toBe(0);
});

it('exclui a própria forma de pagamento', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');
    $forma = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Débito', 'tipo' => 'debito']);

    $this->actingAs($eu)
        ->delete(route('formas-pagamento.destroy', $forma))
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Forma de pagamento excluída com sucesso.']);

    expect(FormaPagamento::find($forma->id))->toBeNull();
});

it('exclui a própria forma de pagamento do tipo crédito sem deixar a extensão inconsistente', function () {
    $eu = Usuario::factory()->create();
    $conta = contaComDonoDe($eu, 'Nubank');
    $forma = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Cartão', 'tipo' => 'credito']);
    CartaoCredito::create([
        'forma_pagamento_id' => $forma->id,
        'limite_total' => 500000,
        'dia_fechamento' => 10,
        'dia_vencimento' => 17,
    ]);

    $this->actingAs($eu)
        ->delete(route('formas-pagamento.destroy', $forma))
        ->assertRedirect(route('contas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Forma de pagamento excluída com sucesso.']);

    expect(FormaPagamento::find($forma->id))->toBeNull()
        ->and(FormaPagamento::withTrashed()->find($forma->id)?->cartaoCredito)->not->toBeNull();
});
