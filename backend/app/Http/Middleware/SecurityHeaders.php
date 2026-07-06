<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Injeta headers HTTP de segurança em todas as respostas do Heimdall.
     * Protege contra Clickjacking, MIME sniffing, XSS e vazamento de informações.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Protege contra Clickjacking — impede que a página seja embutida em iframes externos
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Impede que browsers adivinhem o Content-Type (MIME sniffing attack)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Controla o que é enviado no cabeçalho Referer nas navegações
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Proteção XSS para browsers legados (IE/Edge antigo)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Força HTTPS (aplicado automaticamente quando em HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Desabilita funcionalidades desnecessárias do browser
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Remove header que revela tecnologia usada
        $response->headers->remove('X-Powered-By');
        $response->headers->set('Server', 'Heimdall');

        return $response;
    }
}
