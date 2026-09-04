<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cores_usuario', function (Blueprint $table) {
            $table->foreignUuid('usuario_id')->primary()->constrained('usuarios')->cascadeOnDelete();
            $table->string('cor', 7);
            $table->string('cor_casal', 7)->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE cores_usuario ADD CONSTRAINT cores_usuario_cor_check CHECK (cor ~ '^#[0-9A-Fa-f]{6}$')");
        DB::statement("ALTER TABLE cores_usuario ADD CONSTRAINT cores_usuario_cor_casal_check CHECK (cor_casal IS NULL OR cor_casal ~ '^#[0-9A-Fa-f]{6}$')");

        $usuarios = DB::table('usuarios')->select('id', 'cor')->get();

        foreach ($usuarios as $usuario) {
            DB::table('cores_usuario')->insert([
                'usuario_id' => $usuario->id,
                'cor' => $usuario->cor,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($usuarios->count() === 2) {
            $corCasal = $this->corMedia($usuarios[0]->cor, $usuarios[1]->cor);

            DB::table('cores_usuario')->update(['cor_casal' => $corCasal]);
        }

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('cor');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('cor', 7)->nullable();
        });

        DB::table('usuarios')->orderBy('id')->each(function (object $usuario): void {
            $cor = DB::table('cores_usuario')->where('usuario_id', $usuario->id)->value('cor');

            DB::table('usuarios')->where('id', $usuario->id)->update(['cor' => $cor ?? '#2F6F5E']);
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('cor', 7)->nullable(false)->change();
        });

        Schema::dropIfExists('cores_usuario');
    }

    private function corMedia(string $hexA, string $hexB): string
    {
        [$rA, $gA, $bA] = $this->rgb($hexA);
        [$rB, $gB, $bB] = $this->rgb($hexB);

        return sprintf(
            '#%02X%02X%02X',
            (int) round(($rA + $rB) / 2),
            (int) round(($gA + $gB) / 2),
            (int) round(($bA + $bB) / 2),
        );
    }

    /** @return array{int, int, int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
};
