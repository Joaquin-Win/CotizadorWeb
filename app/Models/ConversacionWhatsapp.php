<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Conversación del bot de WhatsApp con un número. estado: bot (responde la IA) o humano (el bot calla). */
class ConversacionWhatsapp extends Model
{
    protected $table = 'conversaciones_whatsapp';

    protected $fillable = ['telefono', 'estado'];

    public function mensajes(): HasMany
    {
        return $this->hasMany(MensajeWhatsapp::class, 'conversacion_whatsapp_id');
    }
}
