# Site de Streaming de Futebol - Versão 1.0

## Visão Geral

Este projeto é um site simples para listar jogos de futebol e os links de transmissão associados. Possui um painel de administração para gerenciar os jogos e os streams. Esta primeira versão foi construída com PHP e MySQL.

## Pré-requisitos

*   Servidor web com suporte a PHP (versão 7.4 ou superior recomendada).
*   Banco de dados MySQL ou MariaDB.
*   Acesso a um painel de controle de hospedagem como o cPanel (ou conhecimento equivalente para configurar o ambiente).

## Passos de Implantação

Siga estes passos para implantar o site em seu ambiente de hospedagem:

### 1. Download dos Arquivos

Obtenha todos os arquivos do projeto (arquivos `.php`, `.sql` e a pasta `admin/`). Se você clonou o repositório, certifique-se de estar na branch ou commit correto correspondente a esta versão.

### 2. Configuração do Banco de Dados no cPanel

1.  **Faça login no seu cPanel.**
2.  Procure pela seção "Bancos de Dados" e clique em **"Assistente de banco de dados MySQL®"** (ou "MySQL® Databases" se preferir o modo manual).
3.  **Criar um Novo Banco de Dados**:
    *   Digite um nome para o seu banco de dados (ex: `futebol_db`) e clique em "Próxima Etapa".
4.  **Criar um Usuário do Banco de Dados**:
    *   Digite um nome de usuário para o banco de dados (ex: `futebol_user`).
    *   Gere uma senha forte e segura. **Anote o nome do banco de dados, o nome de usuário e a senha, pois você precisará deles mais tarde.**
    *   Clique em "Criar Usuário".
5.  **Adicionar Usuário ao Banco de Dados e Definir Permissões**:
    *   Na página "Adicionar usuário ao banco de dados", certifique-se de que o usuário e o banco de dados corretos estejam selecionados.
    *   Marque a caixa de seleção **"TODOS OS PRIVILÉGIOS"**.
    *   Clique em "Próxima Etapa" ou "Fazer Alterações".

### 3. Importação do Esquema do Banco de Dados

1.  Volte para a tela inicial do cPanel e, na seção "Bancos de Dados", clique em **"phpMyAdmin"**.
2.  No painel esquerdo do phpMyAdmin, selecione o banco de dados que você criou no passo anterior (ex: `futebol_db`).
3.  Clique na aba **"Importar"** no menu superior.
4.  Na seção "Arquivo a importar", clique em **"Escolher arquivo"** e localize o arquivo `schema.sql` que está na raiz do projeto.
5.  Deixe as outras opções com seus valores padrão e clique no botão **"Executar"** (ou "Importar"/"Ir") no final da página.
    *   Você deverá ver uma mensagem de sucesso indicando que a importação foi concluída. As tabelas `matches` e `streams` estarão criadas.

### 4. Upload dos Arquivos do Site

1.  Volte para a tela inicial do cPanel e, na seção "Arquivos", clique em **"Gerenciador de Arquivos"**.
2.  Navegue até o diretório raiz do seu site. Geralmente é `public_html` para o domínio principal, ou um subdiretório se você estiver usando um subdomínio ou uma pasta específica (ex: `public_html/futebol/`).
3.  Clique em **"Carregar"** (ou "Upload") no menu superior do Gerenciador de Arquivos.
4.  Faça o upload de todos os arquivos e pastas do projeto para este diretório:
    *   `index.php`
    *   `match.php`
    *   `config.php`
    *   `schema.sql` (embora já usado, é bom tê-lo no servidor como referência)
    *   A pasta `admin/` (com todos os seus arquivos: `index.php`, `add_match.php`, `add_stream.php`, `delete_match.php`).

### 5. Configuração do `config.php`

1.  Ainda no "Gerenciador de Arquivos" do cPanel, localize o arquivo `config.php` que você acabou de enviar.
2.  Clique com o botão direito sobre ele e selecione **"Edit"** ou **"Code Edit"**.
3.  Você precisará atualizar as seguintes linhas com as informações do banco de dados que você anotou anteriormente:

    ```php
    define('DB_HOST', 'localhost'); // Geralmente 'localhost', mas verifique com seu provedor se for diferente
    define('DB_USER', 'SEU_USUARIO_DO_BANCO_DE_DADOS'); // Substitua pelo usuário que você criou
    define('DB_PASS', 'SUA_SENHA_DO_BANCO_DE_DADOS'); // Substitua pela senha que você criou
    define('DB_NAME', 'SEU_NOME_DO_BANCO_DE_DADOS'); // Substitua pelo nome do banco de dados que você criou
    ```
4.  Salve as alterações no arquivo.

## Acesso ao Site

Após seguir todos os passos:

*   **Site Principal**: Navegue até o seu domínio (ex: `http://seudominio.com` ou `http://seudominio.com/futebol/` se você usou um subdiretório).
*   **Painel de Administração**: Acesse `http://seudominio.com/admin/` (ou `http://seudominio.com/futebol/admin/`).

## Considerações Adicionais

*   **Permissões de Arquivo**: Geralmente, os arquivos PHP precisam de permissão `644` e as pastas `755`. O cPanel costuma lidar bem com isso, mas se você encontrar problemas de permissão, verifique essas configurações.
*   **Segurança do Painel Admin**: Esta versão não inclui um sistema de login para o painel `admin/`. Em um ambiente de produção, é highly recomendável proteger este diretório (ex: usando a ferramenta "Diretórios protegidos por senha" no cPanel ou implementando um sistema de autenticação PHP).

```
