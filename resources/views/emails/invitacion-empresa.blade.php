<x-mail::message>
# Te invitaron al portal de {{ $invitacion->client->empresa }}

Vas a poder ver tus pedidos, seguimientos y documentos desde un solo lugar. El link vence en 7 días y se puede usar una sola vez.

<x-mail::button :url="$url">
Aceptar invitación
</x-mail::button>

Si no esperabas este correo, ignoralo.

Saludos,<br>
Equipo {{ config('app.name') }}
</x-mail::message>
