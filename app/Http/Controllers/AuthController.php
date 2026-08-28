<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;

class AuthController
{
    /**
     * POST: Inicia sesión validando únicamente el número de nómina.
     */
    public function login(Request $request)
    {
        // 1. Validar que React nos envíe el dato
        $request->validate([
            'numero_nomina' => 'required|string'
        ]);

        // 2. Buscar al usuario en la base de datos
        // Nota: Nuestro "Global Scope" ya garantiza que solo busque usuarios con status = 1 (Activos)
        $usuario = Usuario::where('numero_nomina', $request->numero_nomina)->first();

        // 3. Si no existe, rechazamos el acceso
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Número de nómina incorrecto o usuario dado de baja.'
            ], 401); // 401 = No Autorizado
        }

        // 4. Si existe, Sanctum le genera un Token de seguridad
        $token = $usuario->createToken('wittur_auth_token')->plainTextToken;

        // 5. Devolvemos el usuario y su llave (Token) a React
        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => $usuario,
            'token' => $token
        ], 200);
    }

    /**
     * POST: Cierra la sesión destruyendo el token.
     */
    public function logout(Request $request)
    {
        // El usuario logueado revoca (borra) todos sus tokens activos
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente. Gafete destruido.'
        ], 200);
    }
}