<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Services\ContratoService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContratoController extends Controller
{
    protected $contratoService;

    public function __construct(ContratoService $contratoService)
    {
        $this->contratoService = $contratoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('contratos.view'), 403);

        // Iniciamos la consulta con relaciones para evitar N+1
        $query = Contrato::with([
            'persona',
            'cargo',
            'movimientos.planilla',
            'movimientos.fondoPensiones',
            'movimientos.cargo',
            'movimientos.banco',
            'movimientos.condicion',
            'movimientos.moneda',
            'movimientos.centroCosto'
        ]);

        // Filtro por Nombre de Empleado
        if ($request->filled('search_name')) {
            $term = $request->search_name;
            $query->whereHas('persona', function($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                  ->orWhere('apellido_paterno', 'like', "%{$term}%")
                  ->orWhere('apellido_materno', 'like', "%{$term}%");
            });
        }

        // Filtro por Documento
        if ($request->filled('search_doc')) {
            $term = $request->search_doc;
            $query->whereHas('persona', function($q) use ($term) {
                $q->where('numero_documento', 'like', "%{$term}%");
            });
        }

        // Ordenar: por fecha inicio descendente
        $query->orderBy('inicio_contrato', 'desc');

        // Paginación
        $contratos = $query->paginate(7)->appends($request->all());

        // --- KPIs ---
        $hoy = Carbon::now();
        
        // 1. Total Contratos Históricos
        $total = Contrato::count();

        // 2. Activos
        $activos = Contrato::activos()->count();

        // 3. Por Vencer
        $porVencer = Contrato::activos() // Contratos activos según nuestro scope
            ->whereNotNull('fin_contrato') // Deben tener una fecha de fin definida
            ->whereBetween('fin_contrato', [$hoy->copy()->addDay(), $hoy->copy()->addDays(30)]) // Que estén entre mañana y los próximos 30 días
            ->count();

        $kpis = [
            'total' => $total,
            'activos' => $activos,
            'por_vencer' => $porVencer,
        ];

        return view('contratos.index', compact('contratos', 'kpis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Necesitamos listas para los selects
        // $personas = \App\Models\Persona::select('id_persona', 'nombres', 'apellido_paterno')->get();
        // $cargos = \App\Models\Cargo::all();
        // return view('contratos.create', compact('personas', 'cargos'));
        
        return view('contratos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('contratos.create'), 403);

        // Validar token de sesion
        $tokenData = session()->get('contrato_token');
        if (!$tokenData || $tokenData['token'] !== $request->token) {
            return response()->json([
                'ok' => false,
                'error' => 'Token invalido o expirado. Inicie el proceso nuevamente.'
            ], 400);
        }

        // Verificar que no haya expirado
        if (now()->isAfter($tokenData['expires_at'])) {
            session()->forget('contrato_token');
            return response()->json([
                'ok' => false,
                'error' => 'La sesion ha expirado. Inicie el proceso nuevamente.'
            ], 400);
        }

        // Validar datos (validaciones basicas, la integridad referencial se maneja en BD)
        $validated = $request->validate([
            'token' => 'required|string',
            'id_persona' => 'required|integer',
            'id_cargo' => 'required|integer',
            'id_planilla' => 'required|integer',
            'id_fp' => 'required|integer',
            'id_condicion' => 'required|integer',
            'id_banco' => 'required|integer',
            'id_moneda' => 'required|integer',
            'id_centro_costo' => 'required|integer',
            'inicio_contrato' => 'required|date',
            'fin_contrato' => 'required|date|after:inicio_contrato',
            'haber_basico' => 'required|numeric|min:0',
            'asignacion_familiar' => 'nullable',
            'movilidad' => 'nullable|numeric|min:0',
            'numero_cuenta' => 'nullable|string|max:100',
            'codigo_interbancario' => 'nullable|string|max:20',
            'periodo_prueba' => 'nullable',
        ]);

        $resultado = $this->contratoService->crearContrato($validated, $tokenData['tipo_movimiento']);

        // Limpiar token de sesion
        session()->forget('contrato_token');

        return response()->json($resultado);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Verificar permiso
        if (auth()->user()->cannot('contratos.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar contratos'], 403);
        }

        // Implementar lógica de actualización
        $contrato = Contrato::findOrFail($id);

        $contrato->update([
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'haber_basico' => $request->haber_basico,
        ]);

        return response()->json(['success' => true, 'message' => 'Contrato actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('contratos.delete'), 403);

        // Implementar lógica de eliminación
        // ...
    }

    /**
     * Update the specified movement.
     */
    public function updateMovimiento(Request $request, $id)
    {
        // Verificar permiso
        if (auth()->user()->cannot('contratos.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar movimientos'], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'tipo_movimiento' => 'nullable|string|max:50',
            'id_cargo' => 'nullable|exists:bronze.dim_cargos,id_cargo',
            'id_planilla' => 'nullable|exists:bronze.dim_planillas,id_planilla',
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'haber_basico' => 'required|numeric|min:0',
            'movilidad' => 'nullable|numeric|min:0',
            'asignacion_familiar' => 'required|boolean',
            'id_fp' => 'nullable|exists:bronze.dim_fondos_pensiones,id_fondo',
            'id_condicion' => 'nullable|exists:bronze.dim_condiciones,id_condicion',
            'id_banco' => 'nullable|exists:bronze.dim_bancos,id_banco',
            'id_centro_costo' => 'nullable|exists:bronze.dim_centros_costo,id_centro_costo',
            'id_moneda' => 'nullable|exists:bronze.dim_monedas,id_moneda',
        ]);

        // Buscar el movimiento
        $movimiento = \App\Models\ContratoMovimiento::findOrFail($id);

        // Actualizar el movimiento
        $movimiento->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento actualizado correctamente'
        ]);
    }

    /**
     * Evaluar si se puede crear un contrato (API)
     */
    public function evaluarContrato(Request $request)
    {
        $validated = $request->validate([
            'numero_documento' => 'required|string',
            'fecha_inicio' => 'required|date',
        ]);

        $resultado = $this->contratoService->evaluarContrato(
            $validated['numero_documento'],
            $validated['fecha_inicio']
        );

        return response()->json($resultado);
    }

    /**
     * Obtener historial de contratos de una persona (API)
     */
    public function obtenerHistorial(Request $request)
    {
        $validated = $request->validate([
            'id_persona' => 'required|integer',
        ]);

        // Verificar que la persona existe
        $persona = \App\Models\Persona::find($validated['id_persona']);
        if (!$persona) {
            return response()->json([
                'error' => 'Persona no encontrada'
            ], 404);
        }

        $historial = $this->contratoService->obtenerHistorial($validated['id_persona']);

        return response()->json($historial);
    }

    /**
     * Obtener la fecha de inicio del último contrato de una persona (API)
     */
    public function obtenerUltimoInicio(string $numero_documento)
    {
        $persona = \App\Models\Persona::where('numero_documento', $numero_documento)->first();

        if (!$persona) {
            return response()->json([
                'persona_nombre' => null,
                'ultimo_inicio_contrato' => null,
                'ultimo_fin_contrato' => null,
            ]);
        }

        $ultimoContrato = Contrato::where('id_persona', $persona->id_persona)
            ->orderBy('inicio_contrato', 'desc')
            ->first();
        
        $fechaFin = $ultimoContrato ? ($ultimoContrato->fecha_renuncia ?? $ultimoContrato->fin_contrato) : null;

        return response()->json([
            'persona_nombre' => $persona->nombre_completo,
            'ultimo_inicio_contrato' => $ultimoContrato ? $ultimoContrato->inicio_contrato : null,
            'ultimo_fin_contrato' => $fechaFin,
        ]);
    }
}