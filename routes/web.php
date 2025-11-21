<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\MemorandumController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\SolicitudHorasExtrasPTController;
use App\Http\Controllers\SuspensionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Empleados
    Route::resource('empleados', EmpleadoController::class)->except(['show']);

    Route::post('empleados/download', [EmpleadoController::class, 'download'])->name('empleados.download');

    Route::post('empleados/download-cesados', [EmpleadoController::class, 'downloadCesados'])->name('empleados.download-cesados');

    Route::get('/empleados/{id}/modal', [EmpleadoController::class, 'mostrarEmpleadoModal'])
        ->name('empleados.modal');

    // movimientos
    /*
        incluso si no rendiriza una vista, necesito definir la ruta en web.php
        1. la ruta desde la cual viene la peticion
        2. A que parte de mi controlador se dirigue
        3. el nombre/apodo de la ruta
    */
    Route::post('/movimiento/toggle', [MovimientoController::class, 'toggleEstadoInertia'])
        ->name('movimiento.toggle');

    Route::post('/movimiento/toggle_usuarios', [MovimientoController::class, 'toggleEstadoUsuariosInertia'])
        ->name('movimiento.toggle_usuarios');

    Route::get('/movimientos', [MovimientoController::class, 'indexInertia'])->name('movimientos.index');

    // Areas
    Route::resource('areas', AreaController::class)->except(['show']);
    // Route::post('{area}/restore', [AreaController::class, 'restore'])->name('restore');

    // Empresas
    Route::resource('empresas', EmpresaController::class)->except(['show']);
    // Route::post('{empresa}/restore', [EmpresaController::class, 'restore'])->name('restore');

    // Horarios
    Route::resource('horarios', HorarioController::class)->except(['show', 'destroy']);

    Route::get('/horarios/nuevo', [HorarioController::class, 'create_2'])->name('horarios.create-2');

    Route::get('/horarios/empleados', [HorarioController::class, 'empleadosPorEmpresa'])
        ->name('horarios.empleados');

    Route::post('/horarios/store-multiple', [HorarioController::class, 'storeMultiple'])->name('horarios.store-multiple');

    Route::get('/horarios/getFeriadosEmpleado', [HorarioController::class, 'getFeriadosEmpleado'])->name('horarios.getFeriadosEmpleado');

    // Route::get('/horarios/nuevo', HorarioController::class ()

    // Permisos
    Route::resource('permisos', PermisoController::class)->except(['show']);
    Route::group(['prefix' => 'permisos', 'as' => 'permisos.'], function () {
        Route::get('extras', [PermisoController::class, 'extras'])->name('extras');
        Route::get('{permiso}/horarios', [PermisoController::class, 'showHorarios'])->name('showHorarios');
        Route::get('{permiso}/imprimir', [PermisoController::class, 'imprimir'])->name('imprimir');
        Route::post('{permiso}/upload', [PermisoController::class, 'upload'])->name('upload');

        Route::get('gerencia', [PermisoController::class, 'index_gerencia'])->name('index_gerencia');
        Route::get('rrhh', [PermisoController::class, 'index_rrhh'])->name('index_rrhh');
    });

    // Marcaciones
    Route::resource('marcaciones', MarcacionController::class)->except(['show']);
    Route::group(['prefix' => 'marcaciones', 'as' => 'marcaciones.'], function () {
        Route::get('reales', [MarcacionController::class, 'real'])->name('reales');
        Route::get('ediciones', [MarcacionController::class, 'edicion'])->name('ediciones');
        Route::post('download', [MarcacionController::class, 'download'])->name('download');
        Route::post('{marcacion}/upload', [MarcacionController::class, 'upload'])->name('upload');
        Route::post('pull', [MarcacionController::class, 'pull'])->name('pull');
    });

    // Asistencias
    Route::resource('asistencias', AsistenciaController::class);
    Route::post('asistencias/{asistenciaDetalle}/horas-extra/', [AsistenciaController::class, 'updateHorasExtra'])->name('asistencias.horasExtra');

    // Memorandums
    Route::resource('memorandums', MemorandumController::class)->except(['show']);
    Route::get('memorandums/{memorandum}/imprimir', [MemorandumController::class, 'imprimir'])->name('memorandums.imprimir');

    // Suspensiones
    Route::resource('suspensiones', SuspensionController::class);
    Route::group(['prefix' => 'suspensiones', 'as' => 'suspensiones.'], function () {
        Route::get('{suspension}/imprimir', [SuspensionController::class, 'print'])->name('imprimir');
        Route::post('{suspension}/upload', [SuspensionController::class, 'upload'])->name('upload');
    });

    // Usuarios
    Route::resource('usuarios', UsuarioController::class)->except(['show']);

    // Reportes
    Route::group(['prefix' => 'reportes', 'as' => 'reportes.'], function () {
        Route::get('tareo', [ReporteController::class, 'tareoIndex'])->name('tareo.index');
        Route::post('tareo/download', [ReporteController::class, 'tareoDownload'])->name('tareo.download');
        Route::post('tareo/download-starsoft', [ReporteController::class, 'tareoDownloadStarsoft'])->name('tareo.download.estarsoft');

        Route::get('amonestaciones', [ReporteController::class, 'amonestacionIndex'])->name('amonestaciones.index');
        Route::post('amonestaciones/download', [ReporteController::class, 'amonestacionDownload'])->name('amonestaciones.download');

        Route::get('compensas', [ReporteController::class, 'compensaIndex'])->name('compensas.index');
        Route::post('compensas/download', [ReporteController::class, 'compensaDownload'])->name('compensas.download');

        Route::get('horas-extra', [ReporteController::class, 'extraIndex'])->name('extras.index');
        Route::post('horas-extra/download', [ReporteController::class, 'extraDownload'])->name('extras.download');
    });

    // Settings
    Route::redirect('configuracion', 'configuracion/perfil');
    Route::group(['prefix' => 'configuracion', 'as' => 'configuracion.'], function () {
        // Perfil
        Route::get('perfil', [ProfileController::class, 'edit'])->name('perfil.edit');
        Route::patch('perfil', [ProfileController::class, 'update'])->name('perfil.update');
        Route::delete('perfil', [ProfileController::class, 'destroy'])->name('perfil.destroy');

        // Notificaciones
        Route::get('notificaciones', [NotificationController::class, 'index'])->name('notificaciones.index');
        Route::patch('notificaciones/{notification}', [NotificationController::class, 'update'])->name('notificaciones.update');
        Route::delete('notificaciones/{notification}', [NotificationController::class, 'destroy'])->name('notificaciones.destroy');

        Route::get('appearance', function () {
            return Inertia::render('settings/appearance');
        })->name('appearance');
    });

    // SOLICITUDES PT
    Route::patch('/solicitudes-he-pt/{solicitud}/aprobar', [SolicitudHorasExtrasPTController::class, 'aprobar'])->name('solicitudes-he-pt.aprobar');

    Route::delete('/solicitudes-he-pt/{solicitud}/rechazar', [SolicitudHorasExtrasPTController::class, 'rechazar'])->name('solicitudes-he-pt.rechazar');

    Route::get('/solicitudes-he-pt/{solicitud}/detalle', [SolicitudHorasExtrasPTController::class, 'showDetalleSolicitud'])->name('solicitudes-he-pt.detalle');

    Route::get('/solicitudes-he-pt/rrhh', [SolicitudHorasExtrasPTController::class, 'indexRRHH'])->name('solicitudes-he-pt.rrhh');

    Route::get('/enviar-acumulada', [SolicitudHorasExtrasPTController::class, 'enviarTodaLasSolicitudes']);
});

// routes/web.php

require __DIR__.'/auth.php';
