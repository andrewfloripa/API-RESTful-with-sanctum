# API RESTful com Laravel Sanctum

Esta API fornece uma interface para a gestão de livros e índices relacionados.

## Autenticação

- `POST /api/v1/auth/token` - Obtém um token de autenticação Bearer.

## Livros
 Requer um Bearer token para autenticação.
- `POST /api/v1/livros` - Cria um novo livro.
- `GET /api/v1/livros` - Lista todos os livros.
- `GET /api/v1/livros?titulo=exemplo` - Pesquisa livros por título.
- `GET /api/v1/livros?titulo_do_indice=Beta` - Pesquisa livros no indice do livro.

## Índices
 Requer um Bearer token para autenticação.
- `POST /api/v1/livros/{livroId}/importar-indices-xml` - Importa índices de um livro a partir de um arquivo XML.

## Uso

Para utilizar a API, faça as requisições HTTP para as rotas especificadas com os parâmetros e corpo de requisição conforme necessário.

### Obter Token de Autenticação

Para obter um token de autenticação, envie uma requisição POST para `/api/v1/auth/token` com os seguintes parâmetros no corpo da requisição e o cabeçalho `Accept: application/json`:

```json
{
    "email": "constantinoprogramador@gmail.com",
    "password": "123",
    "device_name": "thunther"
}
```
Obs. execute php artisan db:seed --class=UserSeeder para criar o usuário.
