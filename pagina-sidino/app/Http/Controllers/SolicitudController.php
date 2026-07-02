<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;

class SolicitudController extends Controller
{
    public function store(Request $request)
    {
        $solicitud = Solicitud::create([
            'colegio' => $request->colegio,
            'direccion' => $request->direccion,
            'email' => $request->email,
            'telefono' => $request->telefono
        ]);

        return response()->json([
            'ok' => true,
            'id' => $solicitud->id
        ]);
    }
}