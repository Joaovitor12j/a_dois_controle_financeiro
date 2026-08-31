<?php

namespace App\Http\Controllers;

use App\Services\LogoDevCacheService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogoController extends Controller
{
    public function __construct(private readonly LogoDevCacheService $cache) {}

    public function show(string $nome): StreamedResponse
    {
        $caminho = $this->cache->obterCaminho($nome);

        abort_unless($caminho !== null, 404);

        return Storage::disk('local')->response($caminho, headers: [
            'Cache-Control' => 'public, max-age=2592000, immutable',
        ]);
    }
}
