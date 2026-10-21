<?php

use Illuminate\Support\Facades\Broadcast;

// Se suscribe en el frontend como Echo.private('admin-notifications') (en Pusher: private-admin-notifications).
Broadcast::channel('admin-notifications', fn ($user) => $user->esAdmin());
