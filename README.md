# PluguePlus API (Slim 4 + MySQL)

API RESTful para o projeto PluguePlus, construída em PHP 8+ com Slim 4, PDO e JWT. Pensada para consumo por apps Flutter.

## Requisitos
- PHP 8.1+
- Composer
- MySQL 8+ (ou compatível)
- Apache/Nginx configurado para apontar para `public/` (ou alias com rewrite)

## Instalação
1) Clonar o repositório:
```
git clone https://.../plugueplus-api.git
cd plugueplus-api
```
2) Instalar dependências:
```
composer install
```
3) Criar o arquivo `.env` a partir do exemplo:
```
cp .env.example .env
```
4) Ajustar variáveis no `.env`:
```
APP_DEBUG=true
APP_BASE_PATH=/plugueplus-api   # deixe vazio se o DocumentRoot já aponta para public/

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plugueplus
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4

JWT_SECRET=chave-secreta
JWT_TTL=86400
```
5) Garantir permissão de leitura do `.env` para o usuário do servidor web.
6) Configurar VirtualHost:
   - Opção A (recomendada): `DocumentRoot /var/www/plugueplus-api/public`
   - Opção B (subcaminho `/plugueplus-api`): `Alias /plugueplus-api /var/www/plugueplus-api/public` e `APP_BASE_PATH=/plugueplus-api`
7) Reiniciar/recarregar o servidor web.

## Estrutura
```
public/           # front controller (index.php) + .htaccess
src/
  Config/         # Database.php (PDO)
  Controllers/    # Auth, User, Category, Service, ChargingPoint, Review, Post, Classified
  Helpers/        # ResponseHelper, ValidationHelper
  Middleware/     # AuthMiddleware (JWT)
  Models/         # Modelos para cada tabela
database/         # schemas SQL (schema_mysql.sql, schema_classifieds.sql)
composer.json
.env.example
```

## Banco de Dados
Os schemas estão em `database/schema_mysql.sql` e `database/schema_classifieds.sql`. Importe-os no MySQL antes de iniciar a API.

## Executar em desenvolvimento
```
composer start
```
Abre em `http://localhost:8080/api/v1/ping`.

## Autenticação
- JWT (HS256) com `JWT_SECRET` e `JWT_TTL`.
- Header: `Authorization: Bearer <token>`.
- Registro/login usam `password_hash`/`password_verify`; o hash nunca é retornado.

## Padrão de resposta
```json
{
  "success": true,
  "data": {...},
  "message": "mensagem opcional",
  "errors": {...},
  "meta": {...}
}
```

## Rotas (prefixo /api/v1)
### Ping
- `GET /ping`

### Auth
- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me` (protegida via AuthMiddleware, quando ativado)

### Users
- `GET /users` (paginação)
- `GET /users/{id}`
- `PUT /users/{id}`

### Categories
- `GET /categories`
- `POST /categories`
- `PUT /categories/{id}`
- `DELETE /categories/{id}`

### Services
- `GET /services`
- `GET /services/{id}`
- `POST /services`
- `PUT /services/{id}`
- `DELETE /services/{id}`

### Charging Points
- `GET /charging-points`
- `GET /charging-points/{id}`
- `POST /charging-points`
- `PUT /charging-points/{id}`
- `DELETE /charging-points/{id}`

### Reviews
- `GET /reviews` (filtros: `point_id`, `service_id`)
- `POST /reviews`

### Posts (feed)
- `GET /posts`
- `GET /posts/{id}`
- `POST /posts`
- `POST /posts/{id}/like`
- `DELETE /posts/{id}/like`
- `POST /posts/{id}/comment`
- `GET /posts/{id}/comments`

### Classificados
- `GET /classifieds`
- `GET /classifieds/{id}`
- `POST /classifieds`
- `PUT /classifieds/{id}`
- `DELETE /classifieds/{id}`
- `POST /classifieds/{id}/favorite`
- `DELETE /classifieds/{id}/favorite`

## Paginação
Query params: `page` (padrão 1), `per_page` (padrão 20). Resposta inclui:
```
"meta": {
  "page": 1,
  "per_page": 20,
  "total": 123,
  "last_page": 7
}
```

## Helpers e Middleware
- `ResponseHelper`: padroniza JSON (success/data/message/errors/meta).
- `ValidationHelper`: validações básicas (required, email, min).
- `AuthMiddleware`: valida JWT e injeta `request->getAttribute('user')`.

## Observações
- Models usam PDO e prepared statements.
- Sem soft delete por padrão (não há colunas deleted_at/status para isso). Se precisar, adicionar coluna e ajustar o método `delete`.
- Proteção de rotas por perfil (admin) ainda não implementada; pode ser feita via middleware de autorização. 
