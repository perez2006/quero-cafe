# Quero Cafe

Sistema interno em PHP para controle de cafe da equipe.

## Funcionalidades

- autenticacao local com opcao de fallback LDAP
- dashboard com consumo do mes, saldo, previsao e tendencia
- registros de `trouxe`, `abriu` e `acabou`
- escala semanal manual ou com `AUTOMATICO`
- substituicao automatica de pessoas em ferias/afastamento
- distribuicao justa de responsavel pelo cafe
- sugestao de quem deve trazer cafe considerando ausencias
- trilha de auditoria para usuarios, escala, ausencias e registros
- notificacao via webhook do Teams

## Requisitos

- PHP 8.1 ou superior
- extensao PDO SQLite
- extensao mbstring
- extensao LDAP se quiser autenticacao LDAP
- Apache com `mod_rewrite` ou servidor equivalente

## Estrutura

- `index.php`: front controller e roteamento simples
- `app/Controllers`: fluxo HTTP
- `app/Models`: acesso a dados
- `app/Services`: regras de negocio
- `app/Views`: telas
- `css/`: estilos
- `imagens/`: assets

## Banco de dados

O sistema usa SQLite.

Por padrao, o banco fica em:

```text
../cafe-storage/cafe.db
```

Se quiser outro caminho, defina:

```bash
CAFE_DB_PATH=/caminho/para/cafe.db
```

No primeiro acesso o sistema cria automaticamente as tabelas basicas:

- `usuarios`
- `registros`
- `escala`
- `user_absences`
- `audit_logs`
- `schedule_resolution_cache`

## Variaveis de ambiente

### Aplicacao

- `CAFE_APP_ENV`
- `CAFE_DB_PATH`

### LDAP

- `CAFE_LDAP_HOST`
- `CAFE_LDAP_BASE_DN`
- `CAFE_LDAP_UPN_SUFFIX`

Se nao definir essas variaveis, o sistema usa placeholders de exemplo.

### Teams / Webhook

- `CAFE_WEBHOOK_URL`

O sistema envia notificacoes para o Teams usando webhook HTTP POST.

Exemplo de payload enviado:

```json
{
  "text": "Cafe - Claudionir, e sua vez de fazer o cafe da tarde (sexta-feira)."
}
```

Se nao quiser usar variavel de ambiente, tambem pode gravar o webhook em:

```text
../cafe-storage/webhook_url.txt
```

## Escala automatica

Na tela de configuracao, cada campo da escala aceita:

- um usuario fixo
- `AUTOMATICO`

Com `AUTOMATICO`, o sistema:

- distribui a escala de forma justa
- evita repetir demais a mesma pessoa com base no historico recente
- congela a resolucao por semana
- substitui automaticamente pessoas ausentes

## Ferias e afastamentos

Os periodos cadastrados afetam:

- escala automatica
- substituicao de pessoas fixas ausentes
- calculo de quem esta devendo trazer cafe

## Notificacao do Teams

O script de notificacao e:

```bash
php cafe_notificar.php manha
php cafe_notificar.php tarde
```

Se nenhum periodo for informado, ele escolhe:

- `manha` antes de 12h
- `tarde` depois de 12h

Sugestao de agendamento:

- 07:00 para `manha`
- 12:00 para `tarde`

## Permissoes de arquivos

Em ambiente Linux, o usuario do web server precisa ter acesso de leitura e escrita ao banco e ao storage.

Exemplo:

```bash
mkdir -p /var/www/cafe-storage
chown -R www-data:www-data /var/www/cafe-storage
chmod -R 775 /var/www/cafe-storage
```

Se usar `php_errors.log`, tambem ajuste permissao:

```bash
touch /var/www/html/cafe/php_errors.log
chown www-data:www-data /var/www/html/cafe/php_errors.log
chmod 664 /var/www/html/cafe/php_errors.log
```

## Apache

O projeto inclui `.htaccess` com rewrite para front controller.

Garanta que:

- `mod_rewrite` esteja habilitado
- `AllowOverride All` esteja ativo no VirtualHost da pasta

## Execucao local

Exemplo com servidor embutido do PHP:

```bash
php -S localhost:8080
```

Se for usar Apache, aponte o DocumentRoot para a pasta `cafe`.

## Publicacao

Fluxo basico:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin <SEU_REPOSITORIO_GIT>
git push -u origin main
```
