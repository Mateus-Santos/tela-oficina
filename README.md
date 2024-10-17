<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Passo a passo:

## PRÉ-REQUISITOS PARA RODAR O PROJETO

- XAMPP -   **v3.3.0 (Para conexão com banco de dados Se for Windows)**
- COMPOSER -    **Version 2.6.5 2023-10-06 10:11:52**
- LARAVEL - **Laravel Framework 10.48.17**
- NODE.JS - **v20.10.0**

## APÓS CLONAR O PROJETO

### ACESSAR COM O TERMINAL PASTA DO PROJETO E APLICAR OS SEGUINTES COMANDOS:

- composer install
- npm install

**OBS: EM CASO DA MENSAGEM: (4 vulnerabilities (2 moderate, 2 high) APARCER, APLIQUE ESSE COMANDO NA PASTA DO PROJETO: npm audit fix**
- npm audit fix
- Adicione o arquivo **.env** a raiz do projeto

**POR FIM, ABRA 2 TERMINAIS NA PASTA DO PROJETO E APLIQUE ESSES 2 COMANDOS EM AMBOS:**

- php artisan serve
- npm run dev