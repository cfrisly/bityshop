<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Admin → Configuración → Logos de temporada */
class LogoController extends Controller
{
    public function index()
    {
        $logos = Logo::orderByDesc('es_principal')->orderBy('desde')->get();

        return view('admin.logo.index', [
            'logos' => $logos,
            'actual' => Logo::actual(),
        ]);
    }

    public function create()
    {
        return view('admin.logo.create', ['logo' => new Logo(['activo' => true, 'cada_anio' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request, true);
        $data['imagen'] = $request->file('imagen')->store('logos', 'public');

        $logo = DB::transaction(fn () => $this->guardar(new Logo(), $data));
        flash()->success(__('Logo ":nombre" guardado.', ['nombre' => $logo->nombre]));

        return redirect()->route('tienda.admin.logo.index');
    }

    public function edit(Logo $logo)
    {
        return view('admin.logo.edit', ['logo' => $logo]);
    }

    public function update(Request $request, Logo $logo)
    {
        $data = $this->validar($request, false);
        if ($request->hasFile('imagen')) {
            $anterior = $logo->imagen;
            $data['imagen'] = $request->file('imagen')->store('logos', 'public');
            Storage::disk('public')->delete($anterior);
        }

        DB::transaction(fn () => $this->guardar($logo, $data));
        flash()->success(__('Logo ":nombre" actualizado.', ['nombre' => $logo->nombre]));

        return redirect()->route('tienda.admin.logo.index');
    }

    public function destroy(Logo $logo)
    {
        Storage::disk('public')->delete($logo->imagen);
        $logo->delete();
        flash()->info(__('Logo ":nombre" eliminado.', ['nombre' => $logo->nombre]));

        return redirect()->route('tienda.admin.logo.index');
    }

    private function validar(Request $request, bool $nuevo): array
    {
        $esPrincipal = $request->boolean('es_principal');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            // Sin SVG: puede llevar código; PNG con fondo transparente se ve mejor sobre el celeste
            'imagen' => ['bail', $nuevo ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'],
            'desde' => [$esPrincipal ? 'nullable' : 'required', 'date'],
            'hasta' => [$esPrincipal ? 'nullable' : 'required', 'date', ...($request->boolean('cada_anio') ? [] : ['after_or_equal:desde'])],
        ], [
            'imagen.required' => __('Sube la imagen del logo.'),
            'imagen.image' => __('Formato no válido. Usa PNG, JPG, WEBP o GIF (SVG no se acepta: expórtalo como PNG).'),
            'imagen.mimes' => __('Formato no válido. Usa PNG, JPG, WEBP o GIF (SVG no se acepta: expórtalo como PNG).'),
            'imagen.max' => __('La imagen no puede pesar más de 2 MB.'),
            'desde.required' => __('Indica desde qué fecha se muestra.'),
            'hasta.required' => __('Indica hasta qué fecha se muestra.'),
            'hasta.after_or_equal' => __('La fecha final tiene que ser igual o posterior a la inicial.'),
        ]);

        $data['es_principal'] = $esPrincipal;
        $data['cada_anio'] = $request->boolean('cada_anio');
        $data['activo'] = $request->boolean('activo');
        // La imagen se guarda aparte (store/update), aquí solo quedan los datos
        unset($data['imagen']);

        return $data;
    }

    private function guardar(Logo $logo, array $data): Logo
    {
        if ($data['es_principal']) {
            // Solo puede haber un logo principal; no lleva fechas
            Logo::where('id', '!=', $logo->id ?? 0)->update(['es_principal' => false]);
            $data['desde'] = null;
            $data['hasta'] = null;
        }
        $logo->fill($data)->save();

        return $logo;
    }
}
