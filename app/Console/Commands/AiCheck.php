<?php

namespace App\Console\Commands;

use App\DTOs\AI\ChatMessage;
use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiException;
use Illuminate\Console\Command;

/**
 * Diagnóstico del proveedor de IA: muestra la configuración efectiva (sin
 * revelar la clave) y hace una llamada mínima para comprobar que responde.
 * Pensado para ejecutarse en la pestaña «Commands» de Laravel Cloud.
 */
class AiCheck extends Command
{
    protected $signature = 'dsle:ai-check';

    protected $description = 'Comprueba la configuración y la conexión con el proveedor de IA.';

    public function handle(AiProvider $provider): int
    {
        $config = config('dsle.ai');
        $key = (string) ($config['api_key'] ?? '');

        $this->table(['Ajuste', 'Valor'], [
            ['DSLE_AI_PROVIDER', $config['provider'] ?? '(vacío)'],
            ['DSLE_AI_BASE_URL', $config['base_url']],
            ['DSLE_AI_MODEL', $config['model']],
            ['DSLE_AI_API_KEY', $key === '' ? '(vacía)' : 'definida, '.strlen($key).' caracteres'.($key !== trim($key) ? ' ⚠ con espacios al inicio o al final' : '')],
            ['DSLE_AI_MAX_TOKENS', (string) $config['max_tokens']],
            ['DSLE_AI_INTEGRITY', $config['integrity'] ? 'true' : 'false'],
            ['DSLE_AI_REASONING_EFFORT', $config['reasoning_effort'] ?? '(no se envía)'],
            ['Proveedor activo', $provider->name().' ('.$provider->model().')'],
        ]);

        if ($provider->name() === 'stub') {
            $this->warn('Se está usando el modo sin conexión: revisa DSLE_AI_PROVIDER=openai y DSLE_AI_API_KEY, y vuelve a publicar el entorno.');

            return self::FAILURE;
        }

        try {
            $response = $provider->chat([ChatMessage::user('Responde solo con la palabra: ok')]);
        } catch (AiException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Conexión correcta. Respuesta del modelo: '.$response->content);

        return self::SUCCESS;
    }
}
