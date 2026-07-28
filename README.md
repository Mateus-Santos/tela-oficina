<div align="center">

<img src="public/img/logo/Logo-Vertical.png" width="220">

# 🚗 SOS Mecânica

### Sistema Inteligente para Gerenciamento de Oficinas Mecânicas

<p>
Uma plataforma desenvolvida para otimizar o gerenciamento de oficinas mecânicas, centralizando clientes, veículos, ordens de serviço, produtos, vendas e acompanhamento de manutenções em uma única aplicação.
</p>

<br>

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php)
![Docker](https://img.shields.io/badge/Docker-Laravel%20Sail-2496ED?style=for-the-badge&logo=docker)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql)
![NodeJS](https://img.shields.io/badge/Node.js-22.x-339933?style=for-the-badge&logo=node.js)
![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=for-the-badge&logo=bootstrap)
![License](https://img.shields.io/badge/license-MIT-success?style=for-the-badge)

</div>

---

# 📖 Sobre o Projeto

O **SOS Mecânica** é um sistema web desenvolvido para modernizar o fluxo operacional de oficinas mecânicas.

A plataforma concentra todas as etapas do atendimento em um único ambiente, permitindo controlar desde o cadastro de clientes e veículos até a abertura de Ordens de Serviço, controle financeiro, produtos, serviços e acompanhamento das manutenções.

O principal objetivo do projeto é reduzir processos manuais, aumentar a produtividade da equipe e proporcionar uma experiência mais rápida tanto para funcionários quanto para clientes.

Além das funcionalidades tradicionais de um sistema de oficina, diversas automações foram implementadas para diminuir o tempo gasto no preenchimento das informações, reduzindo erros e retrabalho.

---

# ✨ Principais Recursos

- 🚗 Cadastro de veículos
- 👤 Cadastro de clientes
- 🔧 Gerenciamento de Ordens de Serviço
- 📦 Controle de produtos
- 🛠 Cadastro de serviços
- 💰 Controle financeiro da O.S.
- 📈 Cálculo automático de valores
- 🔍 Consulta automática de veículo pela placa
- ⚙️ Integração com API
- 📋 Histórico de manutenções
- 📊 Estrutura preparada para expansão
- 📱 Interface responsiva
- 🔒 Controle de autenticação
- ⚡ Atualizações em tempo real via JavaScript

---

# 📸 Demonstração

> **Em breve**

Espaço reservado para GIFs e capturas de tela do sistema.

```
/docs/images/dashboard.png

/docs/images/os.gif

/docs/images/clientes.png
```

---

# 📑 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Principais Recursos](#-principais-recursos)
- [Tecnologias](#-tecnologias-utilizadas)
- [Arquitetura](#-arquitetura-do-projeto)
- [Estrutura de Pastas](#-estrutura-do-projeto)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação](#-instalação)
- [Fluxo de Desenvolvimento](#-fluxo-de-desenvolvimento)
- [Comandos Úteis](#-comandos-úteis)
- [Implementações](#-principais-implementações)
- [Roadmap](#-roadmap)
- [Contribuição](#-contribuindo)
- [Licença](#-licença)

---

# 🚀 Tecnologias Utilizadas

O projeto foi desenvolvido utilizando tecnologias modernas do ecossistema Laravel, priorizando desempenho, facilidade de manutenção e padronização do ambiente de desenvolvimento.

## Back-end

| Tecnologia | Versão |
|------------|--------|
| PHP | 8.5.x |
| Laravel | 13.x |
| Composer | 2.x |
| Laravel Sail | Última versão |

---

## Front-end

| Tecnologia | Versão |
|------------|--------|
| Blade | Laravel |
| Bootstrap | 5.x |
| JavaScript | ES6+ |
| Vite | 8.x |
| Node.js | 22.x |
| npm | 10.x |

---

## Banco de Dados

- MySQL 8

---

## Ambiente de Desenvolvimento

- Docker
- Laravel Sail
- WSL 2
- Ubuntu
- Visual Studio Code
- Git
- GitHub

---

# 🏗 Arquitetura do Projeto

O sistema foi estruturado utilizando a arquitetura MVC (Model-View-Controller), seguindo os padrões recomendados pelo Laravel.

```
Cliente
    │
    ▼
Blade + Bootstrap + JavaScript
    │
    ▼
Controllers
    │
    ▼
Services / Regras de Negócio
    │
    ▼
Models (Eloquent ORM)
    │
    ▼
MySQL
```

Toda a comunicação entre interface e banco de dados ocorre através do framework Laravel, utilizando Eloquent ORM para manipulação dos dados e JavaScript para oferecer uma experiência dinâmica ao usuário.

---

# 🐳 Arquitetura Docker

O ambiente de desenvolvimento é totalmente isolado utilizando Docker através do Laravel Sail.

```
                    Docker

        ┌──────────────────────────┐
        │      laravel.test        │
        │──────────────────────────│
        │ PHP 8.5                  │
        │ Laravel 13               │
        │ Composer                 │
        │ Node.js                  │
        │ npm                      │
        │ Vite                     │
        └────────────┬─────────────┘
                     │
                     │ Rede Docker
                     │
        ┌────────────▼─────────────┐
        │          MySQL           │
        │──────────────────────────│
        │ Banco de Dados           │
        │ Persistência             │
        └──────────────────────────┘
```

Toda alteração realizada nos arquivos do projeto é refletida instantaneamente dentro do container através de **Bind Mounts**, permitindo desenvolvimento em tempo real sem necessidade de copiar arquivos para dentro do Docker.

---

# 📂 Estrutura do Projeto

```
📦 SOS-MECANICA
├── app/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   └── Services/
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
│
├── public/
│   ├── build/
│   ├── img/
│   └── index.php
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│
├── routes/
│
├── storage/
│
├── tests/
│
├── vendor/
│
├── compose.yaml
├── composer.json
├── package.json
└── vite.config.js
```

---

# ⚙ Principais Características da Arquitetura

- ✅ Ambiente totalmente containerizado
- ✅ Mesmo ambiente para toda a equipe
- ✅ Laravel Sail como padrão de desenvolvimento
- ✅ Banco de dados isolado em container próprio
- ✅ Atualizações instantâneas através de Bind Mount
- ✅ Assets compilados utilizando Vite
- ✅ Estrutura preparada para produção
- ✅ Fácil escalabilidade para novos módulos

---

# 💡 Filosofia do Projeto

O projeto foi desenvolvido buscando manter um código organizado, modular e de fácil manutenção.

Alguns princípios adotados durante o desenvolvimento:

- Separação clara das responsabilidades.
- Reutilização de componentes.
- Interfaces intuitivas.
- Redução de retrabalho através de automações.
- Código preparado para futuras integrações.
- Facilidade de onboarding para novos desenvolvedores.

# 📋 Pré-requisitos

Antes de iniciar o projeto, certifique-se de possuir os seguintes softwares instalados.

| Software | Versão Recomendada |
|----------|--------------------|
| Git | Última versão |
| Docker Desktop | Última versão |
| WSL 2 (Windows) | Atualizado |
| Ubuntu (WSL) | 22.04+ |
| Visual Studio Code | Última versão |

> **Observação**
>
> O projeto utiliza **Laravel Sail**, portanto **não é necessário instalar PHP, Composer, Apache ou MySQL diretamente no sistema operacional**, pois todas essas dependências são executadas dentro dos containers Docker.

---

# 🚀 Instalação

## 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/sos-mecanica.git

cd sos-mecanica
```

---

## 2. Crie o arquivo de configuração

Copie o arquivo de exemplo para criar seu ambiente local.

```bash
cp .env.example .env
```

Caso esteja utilizando Windows com PowerShell:

```powershell
copy .env.example .env
```

---

## 3. Instale as dependências do Composer

Caso seja o primeiro clone do projeto:

```bash
composer install
```

---

## 4. Inicie os containers Docker

O projeto utiliza o **Laravel Sail** para gerenciar todo o ambiente de desenvolvimento.

Suba os containers executando:

```bash
./vendor/bin/sail up -d
```

Caso já tenha configurado o alias do Sail:

```bash
sail up -d
```

Na primeira execução o Docker irá:

- baixar as imagens necessárias;
- construir o ambiente da aplicação;
- iniciar o container do Laravel;
- iniciar o container do MySQL.

Esse processo pode levar alguns minutos.

---

# 🔑 Configuração Inicial

Após iniciar os containers, execute os comandos abaixo.

## Gerar a chave da aplicação

```bash
sail artisan key:generate
```

---

## Executar as migrations

```bash
sail artisan migrate
```

Caso deseje popular o banco com dados de teste:

```bash
sail artisan migrate:fresh --seed
```

---

## Instalar as dependências do Front-end

```bash
sail npm install
```

---

## Iniciar o Vite

Durante o desenvolvimento utilize:

```bash
sail npm run dev
```

Para gerar os arquivos otimizados para produção:

```bash
sail npm run build
```

---

# 🌐 Executando a Aplicação

Com os containers iniciados, acesse:

```
http://localhost
```

Caso a porta HTTP tenha sido alterada, utilize a porta configurada no arquivo `.env`.

---

# 🐳 Laravel Sail

O Laravel Sail fornece uma camada de abstração sobre o Docker, simplificando a execução de comandos do ecossistema Laravel.

Ao invés de executar comandos diretamente no sistema operacional, todos os processos acontecem dentro do container da aplicação, garantindo que toda a equipe utilize exatamente o mesmo ambiente de desenvolvimento.

---

# ⚡ Fluxo Diário de Desenvolvimento

Na maioria dos dias de trabalho, basta seguir o fluxo abaixo.

## 1. Iniciar os containers

```bash
sail up -d
```

---

## 2. Verificar se existe alguma atualização

```bash
git pull
```

---

## 3. Caso existam novas dependências

PHP

```bash
sail composer install
```

JavaScript

```bash
sail npm install
```

Banco de dados

```bash
sail artisan migrate
```

---

## 4. Iniciar o Vite

```bash
sail npm run dev
```

---

## 5. Desenvolver normalmente

Toda alteração realizada nos arquivos é sincronizada automaticamente com o container através do Bind Mount do Docker.

Não é necessário copiar arquivos nem reiniciar a aplicação após cada alteração.

---

## 6. Encerrar o ambiente

Ao finalizar o desenvolvimento:

```bash
sail stop
```

Ou, caso queira remover completamente os containers em execução:

```bash
sail down
```

---

# 📌 Comandos Úteis

| Ação | Comando |
|------|----------|
| Iniciar containers | `sail up -d` |
| Parar containers | `sail stop` |
| Remover containers | `sail down` |
| Reiniciar containers | `sail restart` |
| Acessar shell do container | `sail shell` |
| Instalar dependências PHP | `sail composer install` |
| Adicionar pacote Composer | `sail composer require vendor/pacote` |
| Atualizar dependências | `sail composer update` |
| Instalar dependências Node | `sail npm install` |
| Executar Vite | `sail npm run dev` |
| Gerar build de produção | `sail npm run build` |
| Executar migrations | `sail artisan migrate` |
| Recriar banco com seed | `sail artisan migrate:fresh --seed` |
| Executar seeders | `sail artisan db:seed` |
| Limpar cache | `sail artisan optimize:clear` |
| Executar testes | `sail artisan test` |
| Abrir Tinker | `sail artisan tinker` |
| Ver rotas | `sail artisan route:list` |
| Ver logs | `sail logs` |

---

# ⚠️ Resolução de Problemas

## A porta 80 já está em uso

Verifique se outro servidor web (como Apache/XAMPP) está utilizando a porta HTTP.

Pare o serviço ou altere a porta configurada no Docker.

---

## A porta 3306 está ocupada

Caso exista uma instalação local do MySQL, altere a variável:

```env
FORWARD_DB_PORT=33061
```

Depois reinicie os containers:

```bash
sail down

sail up -d
```

---

## Alterações não aparecem no navegador

Limpe os caches da aplicação:

```bash
sail artisan optimize:clear
```

Caso necessário, reinicie o Vite:

```bash
sail npm run dev
```

---

## Reconstruir completamente o ambiente

Se ocorrer algum problema durante a configuração:

```bash
sail down -v

sail build --no-cache

sail up -d
```

Isso recriará os containers utilizando uma nova imagem, preservando a configuração do projeto.
