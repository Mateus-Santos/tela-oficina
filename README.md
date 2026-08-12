<div align="center">

<img src="public/img/logo/Logo-Vertical.png" width="180">

# 🚗 SOS Mecânica

### Sistema de gerenciamento para oficinas mecânicas

Sistema web desenvolvido para centralizar a operação de uma oficina mecânica, incluindo clientes, veículos, ordens de serviço, produtos, serviços e vendas.

<br>

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php)
![Docker](https://img.shields.io/badge/Docker-Laravel%20Sail-2496ED?style=for-the-badge&logo=docker)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?style=for-the-badge&logo=mysql)
![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=for-the-badge&logo=bootstrap)

</div>

---

## 📖 Sobre

O **SOS Mecânica** é um sistema web desenvolvido para gerenciamento de oficinas mecânicas.

A aplicação centraliza o cadastro e acompanhamento de clientes e veículos, ordens de serviço, produtos, serviços e demais processos relacionados à operação da oficina.

O projeto utiliza **Laravel Sail** para fornecer um ambiente de desenvolvimento baseado em Docker, mantendo PHP, extensões, banco de dados e demais dependências isolados do sistema operacional.

---

## ✨ Principais recursos

- 🚗 Cadastro e gerenciamento de veículos
- 👤 Cadastro de clientes
- 🔧 Ordens de Serviço
- 🛠 Cadastro de serviços
- 📦 Controle de produtos
- 💰 Controle de valores e vendas
- 📋 Histórico de manutenções
- 🔍 Consulta de informações de veículos
- 🔌 Integrações com APIs
- 🔐 Autenticação de usuários
- 📱 Interface responsiva
- ⚡ Recursos dinâmicos utilizando JavaScript

---

## 🚀 Tecnologias

### Back-end

- PHP 8.5
- Laravel 13
- Composer
- Laravel Sail

### Front-end

- Blade
- Bootstrap 5
- JavaScript
- Vite 8
- Node.js

### Banco de dados

- MySQL 8.4

### Infraestrutura

- Docker
- Docker Compose
- Laravel Sail

---

## 🐳 Ambiente Docker

O projeto utiliza **Laravel Sail** para executar o ambiente de desenvolvimento.

Os arquivos Docker utilizados pelo projeto ficam versionados no repositório dentro da pasta `docker/`.

O `compose.yaml` define os serviços da aplicação e do banco de dados.

A aplicação utiliza PHP 8.5 e Laravel 13, enquanto o banco de dados utiliza MySQL 8.4.

---

## 📋 Requisitos

Para executar o projeto, é necessário ter instalado:

- Git
- Docker
- Docker Compose

Em ambientes Windows, recomenda-se utilizar **WSL 2 + Ubuntu**.

Não é necessário instalar PHP, MySQL ou Node.js diretamente no sistema operacional.

---

# 🚀 Instalação

## 1. Clonar o projeto

    git clone https://github.com/seu-usuario/sos-mecanica.git
    cd sos-mecanica

## 2. Criar o arquivo `.env`

    cp .env.example .env

Configure as variáveis necessárias no `.env`.

Para o banco de dados executado pelo Docker, utilize:

    DB_HOST=mysql
    DB_PORT=3306

## 3. Instalar as dependências PHP

Caso o diretório `vendor` ainda não exista:

    composer install

## 4. Iniciar os containers

    ./vendor/bin/sail up -d

Na primeira execução, o Docker poderá precisar construir a imagem da aplicação.

## 5. Gerar a chave da aplicação

    ./vendor/bin/sail artisan key:generate

## 6. Executar as migrations

    ./vendor/bin/sail artisan migrate

Para recriar o banco e executar os seeders:

    ./vendor/bin/sail artisan migrate:fresh --seed

> ⚠️ `migrate:fresh` apaga todas as tabelas existentes do banco de dados.

## 7. Instalar as dependências do front-end

    ./vendor/bin/sail npm install

## 8. Executar o Vite

Durante o desenvolvimento:

    ./vendor/bin/sail npm run dev

Para gerar os arquivos otimizados:

    ./vendor/bin/sail npm run build

---

## 🌐 Acessar a aplicação

Com os containers em execução, acesse:

    http://localhost

Caso a porta da aplicação tenha sido alterada no `.env`, utilize a porta configurada.

---

# 💻 Desenvolvimento

## Iniciar o ambiente

    ./vendor/bin/sail up -d

## Verificar os containers

    ./vendor/bin/sail ps

## Parar os containers

    ./vendor/bin/sail stop

## Remover os containers

    ./vendor/bin/sail down

## Acessar o shell do container

    ./vendor/bin/sail shell

---

# 📦 Composer

Instalar dependências:

    ./vendor/bin/sail composer install

Adicionar um pacote:

    ./vendor/bin/sail composer require vendor/pacote

Atualizar dependências:

    ./vendor/bin/sail composer update

---

# 📦 NPM

Instalar dependências:

    ./vendor/bin/sail npm install

Executar o Vite:

    ./vendor/bin/sail npm run dev

Gerar build:

    ./vendor/bin/sail npm run build

---

# 🧰 Laravel

Executar migrations:

    ./vendor/bin/sail artisan migrate

Limpar caches:

    ./vendor/bin/sail artisan optimize:clear

Listar rotas:

    ./vendor/bin/sail artisan route:list

Abrir o Tinker:

    ./vendor/bin/sail artisan tinker

Executar testes:

    ./vendor/bin/sail artisan test

Ver status das migrations:

    ./vendor/bin/sail artisan migrate:status

---

# 📂 Estrutura do projeto

    SOS-MECANICA/
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── docker/
    │   ├── 8.5/
    │   └── mysql/
    ├── public/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── tests/
    ├── .env.example
    ├── compose.yaml
    ├── composer.json
    ├── package.json
    └── vite.config.js

---

# 🔄 Fluxo Git

O desenvolvimento utiliza branches para separar o desenvolvimento da versão de produção.

    development
         │
         │ desenvolvimento e testes
         ▼
       master
         │
         │ deploy
         ▼
      Produção

As alterações devem ser desenvolvidas e testadas na branch `development` antes de serem incorporadas à `master`.

O processo de deploy está configurado em:

    .github/workflows/deploy.yml

---

# 🧪 Testes

Os testes automatizados podem ser executados dentro do container:

    ./vendor/bin/sail artisan test

---