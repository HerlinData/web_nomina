<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('personas.view'), 403);

        // Iniciamos la consulta
        $query = Persona::with('contratos');

        // Filtro por Nombre (Busca en Nombres o Apellidos)
        if ($request->filled('search_name')) {
            $term = $request->search_name;
            $query->where(function($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                  ->orWhere('apellido_paterno', 'like', "%{$term}%")
                  ->orWhere('apellido_materno', 'like', "%{$term}%");
            });
        }

        // Filtro por Documento
        if ($request->filled('search_doc')) {
            $query->where('numero_documento', 'like', "%{$request->search_doc}%");
        }

        // Paginamos el resultado filtrado
        $personas = $query->paginate(7)->appends($request->all());

        // KPIs (calculados sobre el total)
        $totalPersonas = Persona::count();
        $nuevas = Persona::whereMonth('fecha_registro', Carbon::now()->month)
                         ->whereYear('fecha_registro', Carbon::now()->year)
                         ->count();

        $hoy = Carbon::now()->format('Y-m-d');
        $activas = Persona::whereHas('contratos', function($q) use ($hoy) {
            $q->where('inicio_contrato', '<=', $hoy)
              ->where(function($sub) use ($hoy) {
                  $sub->whereNull('fecha_renuncia')
                      ->whereNull('fin_contrato')
                      ->orWhere('fecha_renuncia', '>=', $hoy)
                      ->orWhere('fin_contrato', '>=', $hoy);
              });
        })->count();

        $kpis = [
            'total' => $totalPersonas,
            'nuevas' => $nuevas,
            'activas' => $activas,
        ];

        $paises = DB::table('bronze.dim_paises')->orderBy('nombre')->get();
        $departamentos = DB::table('bronze.dim_departamentos')->orderBy('nombre')->get();
        $provincias = DB::table('bronze.dim_provincias')->orderBy('nombre')->get();
        $distritos = DB::table('bronze.dim_distritos')->orderBy('nombre')->get();

        return view('personas.index', compact('personas', 'kpis', 'paises', 'departamentos', 'provincias', 'distritos'));
    }

    public function create()
    {
        return view('personas.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('personas.create'), 403);

        $uniqueTable = config('database.default').'.bronze.dim_persona';
        $validated = $request->validate(
            [
                'tipo_documento' => 'required',
                'numero_documento' => [
                    'required',
                    'max:20',
                    Rule::unique($uniqueTable, 'numero_documento'),
                ],
                'nombres' => 'required|max:255',
                'apellido_paterno' => 'required|max:255',
                'apellido_materno' => 'nullable|max:255',
                'fecha_nacimiento' => 'nullable|date',
                'genero' => 'nullable',
                'pais' => 'nullable',
                'departamento' => 'nullable',
                'provincia' => 'nullable',
                'distrito' => 'nullable',
                'direccion' => 'nullable|max:255',
                'numero_telefonico' => 'nullable|max:50',
                'correo_electronico_personal' => 'nullable|email|max:255',
                'correo_electronico_corporativo' => 'nullable|email|max:255',
            ],
            [
                'numero_documento.unique' => 'Persona ya se encuentra en la base de datos',
            ]
        );

        Persona::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Persona registrada correctamente',
        ]);
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->cannot('personas.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar personas'], 403);
        }

        $uniqueTable = config('database.default').'.bronze.dim_persona';
        $validated = $request->validate(
            [
                'tipo_documento' => 'required',
                'numero_documento' => [
                    'required',
                    'max:20',
                    Rule::unique($uniqueTable, 'numero_documento')->ignore($id, 'id_persona'),
                ],
                'nombres' => 'required|max:255',
                'apellido_paterno' => 'required|max:255',
                'apellido_materno' => 'nullable|max:255',
                'fecha_nacimiento' => 'nullable|date',
                'genero' => 'nullable',
                'pais' => 'nullable',
                'departamento' => 'nullable',
                'provincia' => 'nullable',
                'distrito' => 'nullable',
                'direccion' => 'nullable|max:255',
                'numero_telefonico' => 'nullable|max:50',
                'correo_electronico_personal' => 'nullable|email|max:255',
                'correo_electronico_corporativo' => 'nullable|email|max:255',
            ],
            [
                'numero_documento.unique' => 'Persona ya se encuentra en la base de datos',
            ]
        );

        $persona = Persona::findOrFail($id);
        $persona->update($validated);

        return response()->json(['success' => true, 'message' => 'Persona actualizada correctamente']);
    }
}
