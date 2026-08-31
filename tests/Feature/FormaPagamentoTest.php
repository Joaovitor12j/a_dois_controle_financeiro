<?php

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
        ->put(route('formas-pagamento.update', $forma), ['nome' => 'Débito Nubank', 'tipo' => 'debito'])
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
