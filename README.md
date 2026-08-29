# A Dois - Controle Financeiro

App de controle financeiro privado para casal (2 usuários fixos, sem cadastro público). Migração de uma stack Next.js + Supabase + Drizzle para Laravel + Inertia, preservando as regras de negócio já validadas em produção.

## Stack

- Laravel 12 · PHP 8.3 · Postgres 17
- Breeze (preset Inertia + React + TypeScript)
- Pest (banco de teste Postgres real, não SQLite)
- Docker (Dockerfile + docker-compose, sem Sail) · Vite local (fora do container)

## Ambiente local

Pré-requisitos: Docker, Node instalado localmente.

```bash
docker-compose up -d
npm install
npm run dev
```

- App: http://localhost:8083
- Postgres: localhost:5487
- Vite (HMR): localhost:5183

## Testes

```bash
docker-compose exec app php artisan test
```

## Documentação

- [`regras-de-negocio.md`](./regras-de-negocio.md) — regras de domínio (recorrência, despesas, saldo individual, categorias/tags)
- [`CLAUDE.md`](./CLAUDE.md) — decisões de arquitetura e convenções para trabalhar neste repositório com Claude
