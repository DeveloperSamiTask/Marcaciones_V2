<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Notifications\NotificacionAsistencia;
use App\Notifications\NotificacionSuspension;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        return Inertia::render('settings/notification', [
            'notificaciones' => Auth::user()->notifications,
        ]);
    }

    public function update(DatabaseNotification $notification)
    {
        $notification->markAsRead();
        if($notification->type === NotificacionAsistencia::class){
            return to_route('asistencias.show', $notification->data['asistenciaId']);
        }
        if($notification->type === NotificacionSuspension::class){
            return to_route('suspensiones.index');
        }
        return back();
    }

    public function destroy(DatabaseNotification $notification)
    {
        $notification->delete();
        return back();
    }

}
