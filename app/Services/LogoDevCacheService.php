<?php

namespace App\Services;

use App\Support\LogoDev;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class LogoDevCacheService
{
    private const DISCO = 'local';

    private const DIRETORIO = 'logos';

    public function obterCaminho(string $nome): ?string
    {
        $caminho = self::DIRETORIO.'/'.md5($nome).'.png';

        if (Storage::disk(self::DISCO)->exists($caminho)) {
            return $caminho;
        }

        $resposta = Http::get(LogoDev::getLogoUrlByName($nome, [
            'size' => 96,
            'format' => 'png',
            'fallback' => '404',
        ]));

        if ($resposta->failed()) {
            return null;
        }

        Storage::disk(self::DISCO)->put($caminho, $resposta->body());

        return $caminho;
    }
}
