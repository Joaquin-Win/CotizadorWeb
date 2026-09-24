<?php

namespace App\Console\Commands;

use App\Services\Transoft\TransoftClient;
use Illuminate\Console\Command;

class TransoftTestPrecarga extends Command
{
    protected $signature = 'transoft:test-precarga';
    protected $description = 'Envía una precarga de prueba a Transoft (ambiente de test)';

    public function handle()
    {
        $payload = [
            'CodigoSeguimiento'       => 'TEST-' . now()->timestamp,
            'CuitTransportista'       => ['Numero' => config('services.transoft.transportista_cuit')],
            'RazonSocialDadorDeCarga' => 'Empresa de Prueba S.A.',
            'CuitDadorDeCarga'        => ['Numero' => config('services.transoft.dador_cuit')],
            'RazonSocialRemitente'    => 'Empresa de Prueba S.A.',

            // Origen
            'CodigoPostalOrigen'  => '1000',
            'PaisOrigen'          => 'ARG',
            'CalleOrigen'         => 'Castro',
            'NumeroOrigen'        => 235,
            'PisoOrigen'          => '1',
            'DepartamentoOrigen'  => 'C',
            'ObservacionOrigen'   => 'sin timbre',

            // Destino
            'RazonSocialDestinatario' => 'Cliente de Prueba',
            'CodigoPostalDestino'     => '3300',
            'PaisDestino'             => 'ARG',
            'CalleDestino'            => 'Av. Siempre Viva',
            'NumeroDestino'           => 742,
            'PisoDestino'             => '',
            'DepartamentoDestino'     => '',
            'ObservacionDestino'      => '',

            // Contacto
            'ContactoNombre'    => 'Juan Prueba',
            'ContactoDocumento' => 12345678,
            'ContactoTelefono'  => '0376-4123456',
            'ContactoEmail'     => 'test@test.com',

            'Observacion' => 'Precarga de prueba',

            // Detalles de bultos (formato v3)
            'Detalles' => [
                [
                    'Bultos'         => 1,
                    'Kilos'          => 5,
                    'M3'             => 0.06,
                    'ValorDeclarado' => 10000,
                    'Descripcion'    => 'Bulto de prueba',
                ],
            ],

            // Documentos asociados
            'DocumentosAsociados' => [
                [
                    'Letra'  => 'X',
                    'Centro' => '00001',
                    'Numero' => '00000001',
                ],
            ],
        ];

        $client = new TransoftClient();

        $this->info('Enviando precarga a Transoft...');
        $this->line('Payload: ' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $r = $client->addPrecarga($payload);

        $this->line("Status: {$r->status()}");
        $this->line(json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $r->successful() ? self::SUCCESS : self::FAILURE;
    }
}
