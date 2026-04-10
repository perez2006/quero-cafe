# Quero Cafe

Sistema interno em PHP para controle de cafe da equipe, com:

- autenticacao local/LDAP
- dashboard de consumo
- escala manual ou `AUTOMATICO`
- ferias e afastamentos
- sugestao justa de responsavel
- previsao e tendencia de consumo
- trilha de auditoria

## Requisitos

- PHP 8.1+
- SQLite
- Apache com rewrite ou servidor equivalente

## Configuracao

Defina variaveis de ambiente conforme necessario:

- `CAFE_DB_PATH`
- `CAFE_WEBHOOK_URL`
- `CAFE_LDAP_HOST`
- `CAFE_LDAP_BASE_DN`
- `CAFE_LDAP_UPN_SUFFIX`
- `CAFE_APP_ENV`

Por padrao, o banco fica em `../cafe-storage/cafe.db`.

## Observacoes

- Este repositorio foi sanitizado para publicacao.
- Nenhum banco real, cookie, log ou segredo operacional deve ser versionado.
- O schema basico e criado automaticamente no primeiro acesso.

## Publicacao

Depois de criar um repositorio Git remoto:

```bash
git init
git add .
git commit -m "Initial sanitized publish"
git branch -M main
git remote add origin <SEU_REPOSITORIO_GIT>
git push -u origin main
```
