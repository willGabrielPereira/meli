## Como instalar e rodar?

1. Clone o repositório:
   ```bash
   git clone https://github.com/willGabrielPereira/meli
   cd meli
   ```

2. Prepare o ambiente:
   - Duplique o arquivo `.env.example` renomeando-o para `.env`
   - Inicialize os contâineres Docker (banco MySQL, Redis, App Laravel, RabbitMQ e Mockoon):
   ```bash
   docker compose up -d
   ```

3. Não é necessário gerar app key ou rodar migrações manualmente, o dockerfile já está configurado para fazer isso

---

## Comandos do Sistema

### 1. Buscar Anúncios (Producer)
Para conectar no Mock (Mercado Livre) e trazer **todos** os anúncios de um vendedor específico e enviá-los para a fila de processamento:
```bash
php artisan app:search-products 252254392
```

### 2. Processar a Fila (Consumer / Worker)
Os IDs de produtos enviados para a fila não caem no banco de dados sozinhos. Você deve iniciar o operário que esvaziará e processará os produtos paralelamente inserindo-os no banco de dados. Mantenha esse comando rodando:
```bash
php artisan rabbitmq:consume
```
*(Caso ocorram erros repetidos e você precise limpar a fila presa, utilize o comando auxiliar: `php artisan rabbitmq:queue-purge default`)*

---

## API

Os produtos salvos pelo Worker do RabbitMQ ficam guardados no MySQL e podem ser listados por um endpoint leve construído na aplicação.

### **GET `/`**
Retorna a listagem dos produtos sincronizados, utilizando os padrões de paginação nativa da aplicação.

**Parâmetros suportados na Query String:**
- `page` (int) - A página atual que deseja visualizar. Padrão: `1`.
- `limit` (int) - Quantos produtos deseja ver na mesma página. Limite Max permitido: `50`. Padrão: `15`.
- `seller` (int) - Opcional. ID do seller do qual você deseja listar exclusivamente os produtos.

**Exemplos de uso:**
- Listar produtos (configuração normal):
  `http://localhost:8080/`

- Listar a página 3 contendo apenas 5 produtos cada:
  `http://localhost:8080/?page=3&limit=5`

**Resposta de Sucesso (200 OK - Paginador Completo)**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "meli_id": "MLB009000001",
      "seller": 252254392,
      "title": "Notebook Dell Inspiron 15",
      "status": "active",
      "last_sync": "2026-03-17T02:00:00.000000Z",
      "created_at": "2026-03-17T02:00:00.000000Z",
      "updated_at": "2026-03-17T02:00:00.000000Z"
    }
  ],
  "first_page_url": "http://localhost:8080/?page=1",
  "from": 1,
  "last_page": 6,
  "per_page": 5,
  "total": 30
}
```