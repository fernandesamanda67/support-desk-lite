# Support Desk Lite

Projeto Laravel recém criado - estrutura base para desenvolvimento.

## Requisitos

- PHP >= 8.2
- Composer
- Node.js e npm
- SQLite (ou outro banco de dados suportado pelo Laravel)

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/fernandesamanda67/support-desk-lite.git
cd support-desk-lite
```

2. Instale as dependências:
```bash
composer install
npm install
```

3. Configure o ambiente:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure o banco de dados no arquivo `.env` (por padrão usa SQLite)

5. Execute as migrations:
```bash
php artisan migrate
```

## Como rodar

### Desenvolvimento completo (recomendado)
```bash
composer run dev
```

Este comando inicia:
- Servidor Laravel (http://localhost:8000)
- Queue worker
- Logs em tempo real
- Vite para assets

### Apenas o servidor web
```bash
php artisan serve
```

O projeto estará disponível em: `http://localhost:8000`

## Estrutura do Projeto

```
app/
├── Http/
│   └── Controllers/
├── Models/
└── Providers/
```

## Tecnologias

- [Laravel](https://laravel.com) 12.x
- PHP 8.2+
- Tailwind CSS
- Vite

## Licença

Este projeto está sob a licença MIT.
