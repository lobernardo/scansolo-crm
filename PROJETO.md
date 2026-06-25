# ScanSOLO CRM + WhatsApp Agent

> Documento de referência do projeto — leia antes de codar qualquer feature.

---

## O que é este sistema

CRM multi-tenant para operação comercial da ScanSOLO, integrado com agente de WhatsApp via EvolutionAPI v2. O sistema permite gestão de leads, deals e conversas WhatsApp em um único lugar.

**Este sistema é independente da plataforma GPR (ScanSOLO Pipeline).** São dois produtos separados.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 12 |
| Frontend | Livewire 4 + Tailwind CSS 4 |
| Banco | PostgreSQL 18 (container `pgsql`) |
| Cache/Filas | Redis (container `redis`) |
| WhatsApp | EvolutionAPI v2 (container `evolution_v2`) |
| Email dev | Mailpit — http://localhost:8025 |
| Testes | Pest 4 |
| Dev env | Docker (Laravel Sail via `docker compose`) |

---

## Comandos do dia-a-dia (Windows — sem Sail nativo)

```powershell
# Subir containers
docker compose up -d

# Parar containers
docker compose down

# Rodar artisan
docker compose exec laravel.test php artisan <comando>

# Rodar migrations
docker compose exec laravel.test php artisan migrate

# Rodar testes
docker compose exec laravel.test php artisan test --compact

# Instalar pacote PHP
docker compose exec laravel.test composer require <pacote>

# Compilar assets (Vite)
docker compose exec laravel.test npm run build

# Logs da aplicação
docker compose logs laravel.test -f
```

**Acessos locais:**
- App: http://localhost
- Mailpit (emails): http://localhost:8025
- EvolutionAPI: http://localhost:8080
- PostgreSQL: localhost:5432

---

## Personas e RBAC

| Role | Acesso |
|---|---|
| `business_owner` | Tudo dentro do tenant — leads, deals, vendedores, configurações WhatsApp |
| `salesperson` | Apenas leads/deals atribuídos a ele — sem acesso a configurações |

Isolamento multi-tenant obrigatório: toda tabela com dados de negócio tem `tenant_id`. O Tenant Trait aplica global scope automático.

---

## Domínio principal

### Leads
- Únicos por tenant (`tenant_id + email`)
- Criados DENTRO do Kanban (não em tela separada)
- Podem ter múltiplos Deals

### Deals
- Vinculados a um Lead
- Pipeline com estágios fixos:
  1. Novo Lead
  2. Contatado
  3. Qualificado
  4. Proposta Enviada
  5. Negociação
  6. Ganho
  7. Perdido (requer motivo obrigatório)
- Exibidos em Kanban (drag & drop)

### Conversas WhatsApp
- Aba dentro do Deal (não página separada)
- Histórico via EvolutionAPI
- Envio de mensagem direto pelo CRM
- Um número WhatsApp por tenant (MVP)

---

## Integração WhatsApp (EvolutionAPI v2)

- Business Owner conecta via QR Code em Configurações
- URL interna: `http://evolution_v2:8080`
- URL externa (dev): `http://localhost:8080`
- Webhook recebe mensagens em: `POST /api/webhook/whatsapp`
- Autenticação via `AUTHENTICATION_API_KEY` no `.env`

---

## Regras absolutas (nunca violar)

- `AUTHENTICATION_API_KEY` e credenciais do banco só em `.env` — NUNCA hardcoded
- Isolamento de tenant SEMPRE no backend — nunca só na UI
- Permissões verificadas no Controller/Action — nunca confiar no frontend
- Salesperson NUNCA vê dados de outro salesperson do mesmo tenant
- Todo model multi-tenant usa o `TenantTrait` (global scope + fill automático)

---

## Estrutura de arquivos relevante

```
app/
  Models/          — Eloquent models (Lead, Deal, Tenant, User...)
  Livewire/        — Componentes Livewire (Pages em resources/views/pages/)
  Services/        — Lógica de negócio (WhatsAppService, DealService...)
  Enums/           — Enums PHP (DealStage, UserRole...)
  Http/
    Requests/      — Form Requests de validação

database/
  migrations/      — Schema do banco
  seeders/         — Seeds de desenvolvimento

resources/
  views/
    layouts/       — Layout principal (app.blade.php)
    pages/         — Full-page Livewire components
    components/    — Componentes Blade reutilizáveis
```

---

## Próximas features planejadas (ordem sugerida)

- [ ] Auth multi-tenant (registro empresa + primeiro usuário = Business Owner)
- [ ] Convite de Salesperson por email
- [ ] Kanban de Deals (Livewire + drag & drop)
- [ ] Modal criação de Lead/Deal dentro do Kanban
- [ ] Tela de detalhe do Deal (editar, notas, mudar estágio)
- [ ] Integração WhatsApp: conectar QR Code em Configurações
- [ ] Aba de conversa WhatsApp dentro do Deal
- [ ] Webhook receptor de mensagens WhatsApp
- [ ] Dashboard de vendas (Business Owner)
- [ ] Agente IA no WhatsApp (próxima fase)

---

## Convenções de código

- Code em inglês, interface em português (pt-BR)
- Livewire para todas as páginas interativas (`Route::livewire()`)
- Lógica de negócio em Services, não em Controllers/Components
- Enums para valores de domínio (DealStage, UserRole)
- Testes Pest para toda feature nova
- Formatar PHP com Pint: `docker compose exec laravel.test ./vendor/bin/pint`
