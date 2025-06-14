# FutOnline - Plataforma de Streaming e Agendamento Esportivo

## Sobre o Projeto

O FutOnline é uma plataforma web desenvolvida para oferecer aos usuários acesso fácil a agendamentos de partidas esportivas, opções de transmissão ao vivo e informações sobre canais de TV. O sistema conta com um painel administrativo robusto para gerenciamento completo do conteúdo, incluindo partidas, ligas, times, canais de TV, e um sistema de publicidade flexível.

## Funcionalidades Principais

### Para Usuários:

*   **Listagem de Jogos:** Visualização dos próximos jogos, com informações de data, hora, descrição e times.
*   **Filtragem por Liga:** Capacidade de filtrar jogos por ligas específicas.
*   **Página de Detalhes do Jogo:** Informações detalhadas da partida, incluindo player de vídeo embutido para transmissões ao vivo e múltiplas opções de stream.
*   **Canais de TV Ao Vivo:** Seção com listagem de canais de TV e player embutido para assistir programações ao vivo.
*   **Busca:** Ferramenta para buscar jogos e eventos.

### Para Administradores (Painel Administrativo):

*   **Gerenciamento de Conteúdo:**
    *   **Jogos:** Adicionar, editar, excluir e gerenciar detalhes das partidas, incluindo horários, descrições, times envolvidos e imagens de capa.
    *   **Ligas:** Adicionar, editar e excluir ligas esportivas.
    *   **Times:** Adicionar, editar e excluir times, incluindo seus logos e cores primárias.
    *   **Canais de TV:** Adicionar, editar e excluir canais de TV, incluindo logos e URLs de stream.
    *   **Streams:** Gerenciar múltiplas URLs de stream para cada partida.
*   **Configurações do Site:**
    *   Alterar nome do site, logo, imagem de capa padrão para jogos.
    *   Gerenciar configurações de SEO para a homepage.
*   **Sistema Avançado de Gerenciamento de Publicidade:**
    *   **Tipos de Anúncios Suportados:**
        *   **Banner de Imagem:** Anúncios tradicionais com imagem clicável.
        *   **Script Pop-up:** Para códigos de publicidade que geram pop-ups.
        *   **Script Banner:** Para códigos de publicidade em HTML/JavaScript (ex: Google AdSense, banners nativos, formatos customizados).
    *   **Controle de Exibição Detalhado:**
        *   Página Inicial (Homepage)
        *   Páginas de Jogos (área geral de banners)
        *   Páginas de Canais de TV (área geral de banners)
        *   Ao lado esquerdo do player (Páginas de Jogos)
        *   Ao lado direito do player (Páginas de Jogos)
        *   Ao lado esquerdo do player (Páginas de Canais de TV)
        *   Ao lado direito do player (Páginas de Canais de TV)
    *   **Gerenciamento:** Ativar/desativar anúncios, adicionar novos, editar existentes.
*   **Relatórios e Monitoramento (Básico):**
    *   Visualização de usuários online.
    *   Contagem de problemas reportados em itens (jogos/canais).

## Como Usar (Métodos de Uso)

### Navegação no Site (Usuário Final):

1.  **Homepage (`index.php`):** Ponto de partida, exibe os próximos jogos e canais de TV em destaque. Use o menu para navegar por ligas ou acesse diretamente os jogos.
2.  **Página de Jogo (`match.php`):** Acessada ao clicar em um jogo. Assista à transmissão no player principal e explore outras opções de stream listadas. Anúncios podem ser exibidos ao lado do player ou em outras áreas designadas.
3.  **Página de Canal (`channel_player.php`):** Acessada ao clicar em um canal de TV. Assista à transmissão ao vivo. Anúncios podem ser exibidos de forma similar à página de jogo.
4.  **Busca (`search.php`):** Utilize a barra de busca no cabeçalho para encontrar jogos específicos.

### Painel Administrativo (`/admin/`):

1.  **Acesso:** Faça login com suas credenciais de administrador.
2.  **Dashboard (`admin/index.php`):** Visão geral com estatísticas e links rápidos.
3.  **Gerenciamento de Conteúdo:**
    *   Utilize o menu lateral para navegar entre as seções: "Gerenciar Jogos", "Gerenciar Ligas", "Gerenciar Times", "Gerenciar Canais".
    *   Em cada seção, você pode adicionar novos itens, editar ou excluir existentes. Os formulários são intuitivos e guiam o preenchimento dos campos necessários.
4.  **Gerenciamento de Publicidade (`admin/manage_banners.php`):**
    *   **Adicionar Anúncio:**
        1.  Clique em "Adicionar Novo Banner".
        2.  Selecione o "Tipo de Anúncio":
            *   **Imagem:** Faça upload da imagem, defina a URL de destino e texto alternativo.
            *   **Script Pop-up / Script Banner:** Cole o código HTML/JavaScript fornecido pela rede de publicidade no campo "Código do Anúncio".
        3.  Marque as caixas de seleção para definir onde o anúncio deve ser exibido (Homepage, Pág. Jogo, Pág. TV, Ao lado do player Jogo E/D, Ao lado do player TV E/D).
        4.  Defina se o anúncio está "Ativo".
        5.  Salve.
    *   **Editar/Excluir:** Na lista de banners, utilize os botões de ação para modificar ou remover anúncios.
5.  **Configurações do Site (`admin/manage_settings.php`):** Ajuste as configurações globais do site conforme necessário.

## Aspectos de Segurança Implementados

A segurança da plataforma é tratada com seriedade. As seguintes medidas foram implementadas:

*   **Prevenção contra Cross-Site Scripting (XSS):** Todas as saídas de dados para o HTML utilizam `htmlspecialchars()` ou funções equivalentes para escapar caracteres especiais, prevenindo a injeção de scripts maliciosos no navegador dos usuários.
*   **Prevenção contra SQL Injection (SQLi):** O sistema utiliza PDO (PHP Data Objects) com queries parametrizadas (prepared statements) para todas as interações com o banco de dados. Isso garante que entradas de usuário não possam manipular as consultas SQL.
*   **Proteção contra Cross-Site Request Forgery (CSRF):** Formulários críticos no painel administrativo (como adição, edição e exclusão de itens) são protegidos por tokens CSRF. Cada formulário enviado deve conter um token válido, que é verificado no servidor, prevenindo que ações sejam executadas sem o consentimento do administrador.
*   **Autenticação e Gerenciamento de Sessão (Painel Admin):**
    *   O acesso ao painel administrativo é protegido por um sistema de login.
    *   As sessões de administrador são gerenciadas de forma segura. (Detalhes específicos como hashing de senhas devem seguir as melhores práticas - e.g., `password_hash()` e `password_verify()`).
*   **Estrutura de Uploads:** O sistema de upload de imagens para banners, logos, etc., deve ser configurado no servidor para apenas permitir tipos de arquivos válidos e armazená-los em diretórios apropriados, fora do acesso direto de execução.

É crucial manter o servidor e as dependências PHP atualizadas para mitigar vulnerabilidades conhecidas.

## Configuração do Ambiente de Desenvolvimento/Produção

1.  **Pré-requisitos:**
    *   Servidor web com suporte a PHP (Ex: Apache, Nginx).
    *   Banco de dados MySQL.
    *   PHP (versão compatível com o código, e.g., 7.4+ ou 8.x).

2.  **Configuração do Banco de Dados:**
    *   Crie um banco de dados MySQL.
    *   Copie `config.php.example` para `config.php` (se existir o example) ou edite diretamente `config.php` no diretório raiz.
    *   Preencha as constantes `DB_HOST`, `DB_USER`, `DB_PASS`, e `DB_NAME` com suas credenciais do MySQL.
    *   **Importação do Schema SQL:** É fundamental importar os arquivos SQL na ordem correta:
        1.  `schema.sql` ou `schema_completo.sql`: Contém a estrutura inicial da maioria das tabelas. Verifique qual é o mais completo e adequado para seu setup inicial.
        2.  `banners_table.sql`: Se não estiver incluído no schema principal, este arquivo cria a tabela `banners`.
        3.  `update_schema_for_ads.sql`: **Este arquivo é crucial e deve ser aplicado APÓS os schemas base.** Ele contém todas as modificações (`ALTER TABLE`) na tabela `banners` necessárias para o sistema de publicidade completo, incluindo colunas para tipos de anúncio, códigos de script e locais de exibição específicos (como ao lado do player).
        4.  Outros arquivos `update_schema_vX.sql` (se houver): Aplique-os em ordem cronológica, pois são atualizações incrementais do schema.

3.  **Configuração do Servidor Web:**
    *   Configure o DocumentRoot do seu servidor web para apontar para o diretório raiz do projeto.
    *   Certifique-se de que o servidor está configurado para processar arquivos `.php`.
    *   Para Apache, o arquivo `.htaccess` presente no projeto sugere que `mod_rewrite` deve estar habilitado para URLs amigáveis (se aplicável).

4.  **Permissões de Arquivo:**
    *   O diretório `uploads/` e todos os seus subdiretórios (ex: `uploads/banners/`, `uploads/covers/matches/`, `uploads/logos/`) devem ter permissão de escrita para o usuário do servidor web (ex: `www-data`). Isso é necessário para que o upload de imagens funcione.
        ```bash
        # Exemplo de como definir permissões (execute no terminal, no diretório raiz do projeto)
        # CUIDADO: Ajuste conforme seu ambiente e necessidades de segurança.
        # chown -R www-data:www-data uploads
        # chmod -R 755 uploads
        ```

## Acesso ao Painel Administrativo

*   O painel administrativo está localizado no diretório `/admin/` (ex: `http://seusite.com/admin/`).
*   As credenciais de administrador são gerenciadas na tabela `admins` (ou similar) no banco de dados. Para o primeiro acesso, pode ser necessário inserir um registro de administrador manualmente no banco de dados com uma senha hasheada (usando `password_hash()` em PHP, por exemplo).

---

Este README oferece um guia detalhado sobre o projeto FutOnline. Para informações mais específicas sobre lógicas de programação ou funcionalidades não cobertas, consulte o código-fonte e os comentários nele contidos.
