<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EtiquetaQr;
use App\Models\QrCarpeta;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrController extends Controller
{
    public function index(Request $request)
    {
        $carpetas = QrCarpeta::whereNull('parent_id')->with('subcarpetasRecursivas')->get();
        $carpetaSeleccionada = $request->get('carpeta');

        $qrsQuery = EtiquetaQr::where('activo', true);
        if ($carpetaSeleccionada) {
            $qrsQuery->where('carpeta_id', $carpetaSeleccionada);
        }
        $qrs = $qrsQuery->latest()->get();

        $todasLasCarpetas = QrCarpeta::all();

        return view('qrs.index', compact('carpetas', 'qrs', 'todasLasCarpetas', 'carpetaSeleccionada'));
    }

    public function download($id)
    {
        $qr = EtiquetaQr::findOrFail($id);
        $rutaAbsoluta = storage_path('app/public/' . $qr->ruta_archivo);
        
        if (file_exists($rutaAbsoluta)) {
            return response()->download($rutaAbsoluta, Str::slug($qr->titulo) . '.svg');
        }

        return back()->with('error', 'El archivo físico no existe en el servidor.');
    }

    public function destroy($id)
    {
        $qr = EtiquetaQr::findOrFail($id);
        
        if (Storage::disk('public')->exists($qr->ruta_archivo)) {
            Storage::disk('public')->delete($qr->ruta_archivo);
        }
        
        $qr->delete();

        return back()->with('success', 'Código QR eliminado correctamente.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'          => 'required|string|max:255',
            'contenido_datos' => 'required|url',
            'carpeta_id'      => 'required|exists:qr_carpetas,id',
            'color_qr'        => 'nullable|string',
        ]);

        // Cambiamos la extensión a .svg para evitar cualquier dependencia de Imagick
        $nombreArchivo = 'qr_' . time() . '_' . uniqid() . '.svg';
        $rutaRelativa  = 'qrs/' . $nombreArchivo;
        $rutaAbsoluta  = storage_path('app/public/' . $rutaRelativa);

        if (!Storage::disk('public')->exists('qrs')) {
            Storage::disk('public')->makeDirectory('qrs');
        }

        $enlaceFinal = $this->limpiarEnlaceDrive($request->contenido_datos);

        $hex = $request->color_qr ?? '#000000';
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

        // Generamos el QR en formato SVG nativo
        QrCode::format('svg')
              ->size(400)
              ->errorCorrection('H')
              ->margin(2)
              ->color($r, $g, $b)
              ->generate($enlaceFinal, $rutaAbsoluta);

        EtiquetaQr::create([
            'titulo'          => $request->titulo,
            'contenido_datos' => $request->contenido_datos,
            'carpeta_id'      => $request->carpeta_id,
            'ruta_archivo'    => $rutaRelativa,
            'activo'          => true
        ]);

        return back()->with('success', 'Código QR generado y guardado correctamente.');
    }

    private function limpiarEnlaceDrive($url)
    {
        if (str_contains($url, 'drive.google.com')) {
            if (preg_match('/[-\w]{25,}/', $url, $matches)) {
                return "https://drive.google.com/uc?export=download&id=" . $matches[0];
            }
        }
        return $url;
    }

    public function storeCarpeta(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:255',
            'parent_id' => 'nullable|exists:qr_carpetas,id',
        ]);

        QrCarpeta::create([
            'nombre'    => $request->nombre,
            'parent_id' => $request->parent_id,
        ]);

        return back()->with('success', 'Carpeta creada correctamente.');
    }

    public function moverCarpeta(Request $request, $id)
    {
        $request->validate([
            'carpeta_id' => 'required|exists:qr_carpetas,id',
        ]);

        $qr = EtiquetaQr::findOrFail($id);
        $qr->update([
            'carpeta_id' => $request->carpeta_id
        ]);

        return back()->with('success', 'Código QR movido de carpeta correctamente.');
    }

    public function destroyCarpeta($id)
    {
        $carpeta = QrCarpeta::with('subcarpetasRecursivas')->findOrFail($id);

        // Comprobamos si tiene códigos QR dentro o subcarpetas hijas
        $tieneQrs = EtiquetaQr::where('carpeta_id', $id)->exists();
        $tieneSubcarpetas = $carpeta->subcarpetasRecursivas->count() > 0;

        if ($tieneQrs || $tieneSubcarpetas) {
            return back()->with('error', 'No se puede eliminar la carpeta porque contiene archivos o subcarpetas en su interior.');
        }

        $carpeta->delete();

        return back()->with('success', 'Carpeta eliminada correctamente.');
    }
}