<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Crear el middleware de autorización en el constructor
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! Gate::allows('manage-users')) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }
            return $next($request);
        });
    }

    /**
     * Mostrar listado de usuarios
     */
    public function index()
    {
        $currentUser = auth()->user();
        
        // Admin y Superadmin ven a TODOS los usuarios
        $users = User::with('proyectos')->paginate(10);

        return view('usuarios.index', compact('users', 'currentUser'));
    }

    /**
     * Mostrar formulario de creacion
     */
    public function create()
    {
        $proyectos = Proyecto::orderBy('nombre')->get();

        return view('usuarios.create', compact('proyectos'));
    }

    /**
     * Guardar nuevo usuario
     */
    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request)
    {
        // 1. Validamos los campos (¡Eliminamos la validación del password!)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni_nie' => 'required|string|max:20|unique:users,dni_nie',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:user,admin,superadmin',
            'departamento' => 'nullable|string|max:255',
            'tipo_personal' => 'required|in:indefinido,temporal',
            'camiseta' => 'nullable|string|max:20',
            'chaqueta' => 'nullable|string|max:20',
            'sudadera' => 'nullable|string|max:20',
            'pantalon' => 'nullable|string|max:20',
            'calzado' => 'nullable|string|max:20',
            'casco' => 'nullable|string|max:20',
            'guantes' => 'nullable|string|max:20',
            'proyecto_ids' => 'nullable|array',
            'proyecto_ids.*' => 'exists:proyectos,id',
            'telefono' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'nullable|boolean',
        ]);

        // Un admin no puede crear a un superadmin
        if (auth()->user()->role === 'admin' && $validated['role'] === 'superadmin') {
            return back()->withErrors(['role' => 'No tienes permisos para asignar el rol de Super Admin.'])->withInput();
        }

        // 2. Creamos el usuario asignándole una contraseña aleatoria muy segura
        $user = User::create([
            'name' => $validated['name'],
            'apellido' => $validated['apellido'],
            'dni_nie' => $validated['dni_nie'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)), // Generamos un password aleatorio
            'role' => $validated['role'],
            'departamento' => $validated['departamento'] ?? null,
            'tipo_personal' => $validated['tipo_personal'],
            'camiseta' => $validated['camiseta'] ?? null,
            'chaqueta' => $validated['chaqueta'] ?? null,
            'sudadera' => $validated['sudadera'] ?? null,
            'pantalon' => $validated['pantalon'] ?? null,
            'calzado' => $validated['calzado'] ?? null,
            'casco' => $validated['casco'] ?? null,
            'guantes' => $validated['guantes'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => $request->boolean('activo', true),
        ]);

        $user->proyectos()->sync($validated['proyecto_ids'] ?? []);

        // 3. Sincronizamos todos los proyectos si el rol creado es superadmin
        if ($user->role === 'superadmin') {
            $user->syncAllProjectsIfSuperadmin();
        }

        // 4. AÑADIDO: Generamos el token y enviamos el correo automático de Laravel
        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente y correo de configuración enviado.');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(User $user)
    {
        $this->authorize('edit-user', $user);

        // AÑADIDO: Pasamos los proyectos a la vista para poder asignarlos
        $proyectos = Proyecto::orderBy('nombre')->get();

        return view('usuarios.edit', compact('user', 'proyectos'));
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('edit-user', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:user,admin,superadmin',
            'telefono' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
            // AÑADIDO: Validamos los proyectos que llegan desde los checkboxes
            'proyecto_ids' => 'nullable|array',
            'proyecto_ids.*' => 'exists:proyectos,id',
        ]);

        // Validar que no intente cambiar su propio rol
        if ($request->user()->id === $user->id && $request->input('role') !== $user->role) {
            return back()->withErrors(['role' => 'No puedes cambiar tu propio rol.']);
        }

        // Validar cambio de rol según permisos
        if ($request->input('role') !== $user->role) {
            $this->authorize('change-user-role', $user);
            // Un admin no puede asignar 'superadmin'
            if ($request->user()->role === 'admin' && $request->input('role') === 'superadmin') {
                return back()->withErrors(['role' => 'No tienes permisos para asignar ese rol.']);
            }
        }

        $user->update($validated);

        // AÑADIDO: Sincronizar los proyectos asignados
        if ($user->role === 'superadmin') {
            $user->syncAllProjectsIfSuperadmin();
        } else {
            $user->proyectos()->sync($validated['proyecto_ids'] ?? []);
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Cambiar rol de un usuario (AJAX)
     */
    public function changeRole(Request $request, User $user)
    {
        $this->authorize('change-user-role', $user);

        $request->validate([
            'role' => 'required|in:user,admin,superadmin',
        ]);

        // Validar que no sea su propio rol
        if (auth()->id() === $user->id && $request->input('role') !== $user->role) {
            return response()->json(['error' => 'No puedes cambiar tu propio rol.'], 403);
        }

        // Un admin no puede asignar 'superadmin'
        if (auth()->user()->role === 'admin' && $request->input('role') === 'superadmin') {
            return response()->json(['error' => 'No tienes permisos para asignar ese rol.'], 403);
        }

        $user->update(['role' => $request->input('role')]);

        if ($user->role === 'superadmin') {
            $user->syncAllProjectsIfSuperadmin();
        }

        return response()->json(['success' => true, 'message' => 'Rol actualizado correctamente.']);
    }

    /**
     * Cambiar estado de activación
     */
    public function toggleActive(Request $request, User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['error' => 'No puedes cambiar tu propio estado.'], 403);
        }

        $user->update(['activo' => !$user->activo]);

        return response()->json([
            'success' => true,
            'activo' => $user->activo,
            'message' => $user->activo ? 'Usuario activado.' : 'Usuario desactivado.'
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(User $user)
    {
        $this->authorize('delete-user', $user);

        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Mostrar detalles del usuario
     */
    public function show(User $user)
    {
        $this->authorize('view-user', $user);

        $user->load('proyectos');

        return view('usuarios.show', compact('user'));
    }

    /**
     * Mostrar perfil personal
     */
    public function personalShow(User $user, Request $request)
    {
        $this->authorize('view-user', $user);

        $user->load('proyectos');

        $proyectoId = $user->proyectos->first()?->id;

        // Array de datos mockeados
        $datosCompletos = [
            ['fecha' => now()->subDays(3), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7732', 'cantidad' => 1, 'articulo' => 'Casco de Seguridad'],
            ['fecha' => now()->subDays(6), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7650', 'cantidad' => 2, 'articulo' => 'Guantes de Protección'],
            ['fecha' => now()->subDays(10), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'REPOSICIÓN', 'cantidad' => 5, 'articulo' => 'Arnés de Seguridad'],
            ['fecha' => now()->subDays(14), 'estado' => 'Pendiente', 'estado_clase' => 'profile-chip profile-chip--pending', 'ot' => 'OT-7621', 'cantidad' => 1, 'articulo' => 'Cinturón de Trabajo'],
            ['fecha' => now()->subDays(18), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7589', 'cantidad' => 3, 'articulo' => 'Zapatos de Seguridad'],
            ['fecha' => now()->subDays(22), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7512', 'cantidad' => 2, 'articulo' => 'Gafas de Protección'],
            ['fecha' => now()->subDays(26), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7445', 'cantidad' => 1, 'articulo' => 'Mascarilla FFP2'],
            ['fecha' => now()->subDays(30), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7321', 'cantidad' => 4, 'articulo' => 'Chaleco Reflectante'],
        ];

        // Obtener parámetros de filtro
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $articuloBuscar = $request->input('articulo');

        // Filtrar datos
        $datosFiltrados = array_filter($datosCompletos, function ($item) use ($fechaDesde, $fechaHasta, $articuloBuscar) {
            $fecha = $item['fecha'];

            // Filtro de fechas
            if ($fechaDesde && $fecha < \Carbon\Carbon::parse($fechaDesde)->startOfDay()) {
                return false;
            }
            if ($fechaHasta && $fecha > \Carbon\Carbon::parse($fechaHasta)->endOfDay()) {
                return false;
            }

            // Filtro de artículo
            if ($articuloBuscar && stripos($item['articulo'], $articuloBuscar) === false) {
                return false;
            }

            return true;
        });

        //  historicoSalidas con datos filtrados
        $historicoSalidas = array_map(function ($item) {
            return (object) [
                'fecha' => $item['fecha']->format('d M Y'),
                'articulo' => $item['articulo'],
                'cantidad' => $item['cantidad'],
                'ot' => $item['ot'],
                'estado' => $item['estado'],
                'estado_clase' => $item['estado_clase'],
            ];
        }, array_slice($datosFiltrados, 0, 8));

        $tallas = [
            ['label' => 'Camiseta', 'value' => $user->camiseta ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Chaquetón', 'value' => $user->chaqueta ?: '—', 'icon' => 'fa-vest'],
            ['label' => 'Sudadera', 'value' => $user->sudadera ?: '—', 'icon' => 'fa-shirt'],
            ['label' => 'Pantalón', 'value' => $user->pantalon ?: '—', 'icon' => 'fa-bridge'],
            ['label' => 'Calzado', 'value' => $user->calzado ?: '—', 'icon' => 'fa-shoe-prints'],
            ['label' => 'Casco', 'value' => $user->casco ?: '—', 'icon' => 'fa-hard-hat'],
            ['label' => 'Guantes', 'value' => $user->guantes ?: '—', 'icon' => 'fa-hand-paper'],
        ];

        return view('personal.show', compact('user', 'historicoSalidas', 'tallas'));
    }

    /**
     * formulario de edición de ficha de personal 
     */
    public function personalEdit(User $user)
    {
        $this->authorize('edit-user', $user);

        $proyectos = Proyecto::orderBy('nombre')->get();

        return view('personal.edit', compact('user', 'proyectos'));
    }

    /**
     * Actualizar ficha de personal
     */
    public function personalUpdate(Request $request, User $user)
    {
        $this->authorize('edit-user', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'dni_nie' => 'nullable|string|max:20|unique:users,dni_nie,' . $user->id,
            'departamento' => 'nullable|string|max:255',
            'tipo_personal' => 'nullable|in:indefinido,temporal',
            'camiseta' => 'nullable|string|max:20',
            'chaqueta' => 'nullable|string|max:20',
            'sudadera' => 'nullable|string|max:20',
            'pantalon' => 'nullable|string|max:20',
            'calzado' => 'nullable|string|max:20',
            'casco' => 'nullable|string|max:20',
            'guantes' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string|max:500',
            'proyecto_ids' => 'nullable|array',
            'proyecto_ids.*' => 'exists:proyectos,id',
            'activo' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'apellido' => $validated['apellido'] ?? $user->apellido,
            'dni_nie' => $validated['dni_nie'] ?? $user->dni_nie,
            'departamento' => $validated['departamento'] ?? $user->departamento,
            'tipo_personal' => $validated['tipo_personal'] ?? $user->tipo_personal,
            'camiseta' => $validated['camiseta'] ?? $user->camiseta,
            'chaqueta' => $validated['chaqueta'] ?? $user->chaqueta,
            'sudadera' => $validated['sudadera'] ?? $user->sudadera,
            'pantalon' => $validated['pantalon'] ?? $user->pantalon,
            'calzado' => $validated['calzado'] ?? $user->calzado,
            'casco' => $validated['casco'] ?? $user->casco,
            'guantes' => $validated['guantes'] ?? $user->guantes,
            'telefono' => $validated['telefono'] ?? $user->telefono,
            'descripcion' => $validated['descripcion'] ?? $user->descripcion,
            'activo' => $request->boolean('activo', $user->activo),
        ]);

        $user->proyectos()->sync($validated['proyecto_ids'] ?? []);

        return redirect()->route('personal.show', $user->id)->with('success', 'Ficha actualizada correctamente.');
    }
}