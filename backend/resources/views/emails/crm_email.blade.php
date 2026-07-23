@component('mail::message')
# Olá, {{ $clienteNome }}!

{!! nl2br(e($mensagemTexto)) !!}

<br>
@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
 Acesse a 90 Store
@endcomponent

Atenciosamente,<br>
Equipe **{{ config('app.name', '90 Store') }}**
@endcomponent
